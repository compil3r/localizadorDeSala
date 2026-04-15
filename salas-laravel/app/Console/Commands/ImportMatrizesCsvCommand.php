<?php

namespace App\Console\Commands;

use App\Models\Discipline;
use App\Services\MatrizesCsv\MatrizesCsvMapper;
use App\Services\MatrizesCsv\MatrizesCsvParser;
use App\Services\MatrizesCsv\NameNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMatrizesCsvCommand extends Command
{
    protected $signature = 'matrizes:import {--file=../matrizes.csv} {--dry-run} {--create-missing-disciplines}';
    protected $description = 'Importa matrizes.csv em curriculum_matrix (sem telas).';

    public function handle(): int
    {
        $fileOption = (string) $this->option('file');
        $filePath = $this->resolveFilePath($fileOption);

        $dryRun = (bool) $this->option('dry-run');
        $createMissingDisciplines = (bool) $this->option('create-missing-disciplines');

        $rows = MatrizesCsvParser::parse($filePath);
        $maps = MatrizesCsvMapper::buildMaps();

        $courseByCode = $maps['courseByCode'];
        $disciplineByNameNorm = $maps['disciplineByNameNorm'];

        // Pré-validação: se houver curso não encontrado, falhar/relatar (sem criar disciplinas).
        $missingCourseCodes = [];
        $seenCourseCodes = [];
        foreach ($rows as $row) {
            if (!empty($row['parse_error'] ?? false)) {
                continue;
            }

            $courseCodeRaw = (string) ($row['course_code'] ?? '');
            $courseCode = NameNormalizer::normalizeCourseCode($courseCodeRaw);
            if ($courseCode === '' || isset($seenCourseCodes[$courseCode])) {
                continue;
            }
            $seenCourseCodes[$courseCode] = true;

            if (!isset($courseByCode[$courseCode])) {
                $missingCourseCodes[$courseCode] = $courseCodeRaw;
            }
        }

        if ($missingCourseCodes !== []) {
            $this->error('Falha: existem cursos do CSV que não existem no banco (courses.code).');
            foreach ($missingCourseCodes as $codeRaw) {
                $this->line(" - {$codeRaw}");
            }
            return 1;
        }

        $createdDisciplines = 0;
        $conflicts = 0;
        $parseErrors = 0;

        $seenOptionalByKey = [];
        $payloadByKey = [];
        $createdDisciplineIdsByNameNorm = [];

        // Primeiro, resolve disciplina (criando se permitido) e monta payload para upsert.
        foreach ($rows as $row) {
            $lineNo = (int) ($row['line_no'] ?? 0);

            $parseError = !empty($row['parse_error'] ?? false);
            if ($parseError) {
                $parseErrors++;
                continue;
            }

            $semester = (int) ($row['course_semester'] ?? -1);
            if ($semester <= 0) {
                $parseErrors++;
                continue;
            }

            $courseCodeRaw = (string) ($row['course_code'] ?? '');
            $courseCode = NameNormalizer::normalizeCourseCode($courseCodeRaw);
            $courseId = $courseByCode[$courseCode] ?? null;
            if ($courseId === null) {
                // Já deveria ter sido filtrado por missingCourseCodes, mas mantemos segurança.
                $this->warn("Linha {$lineNo}: curso não resolvido ({$courseCodeRaw}).");
                continue;
            }

            $disciplineNameRaw = (string) ($row['discipline_name'] ?? '');
            $disciplineNorm = NameNormalizer::normalize($disciplineNameRaw);
            $disciplineId = $disciplineByNameNorm[$disciplineNorm] ?? null;

            if ($disciplineId === null) {
                if (!$createMissingDisciplines) {
                    $this->error("Falha: disciplina ausente no banco: '{$disciplineNameRaw}'. Rode com --create-missing-disciplines.");
                    return 1;
                }

                // Cria disciplina apenas uma vez por import (por normalização de nome).
                if (!isset($createdDisciplineIdsByNameNorm[$disciplineNorm])) {
                    if ($dryRun) {
                        $createdDisciplineIdsByNameNorm[$disciplineNorm] = -1;
                    } else {
                        $new = Discipline::create([
                            'name' => $disciplineNameRaw,
                            'owning_course_id' => null,
                        ]);
                        $createdDisciplines++;
                        $createdDisciplineIdsByNameNorm[$disciplineNorm] = (int) $new->id;
                    }
                }

                $disciplineId = $createdDisciplineIdsByNameNorm[$disciplineNorm];
                if ($disciplineId <= 0) {
                    // dry-run não sabe o id real; então pula payload e fica só relatório de contagem.
                    continue;
                }

                $disciplineByNameNorm[$disciplineNorm] = $disciplineId;
            }

            $isOptional = (bool) ($row['is_optional'] ?? false);
            $key = "{$courseId}|{$semester}|{$disciplineId}";

            if (isset($seenOptionalByKey[$key]) && $seenOptionalByKey[$key] !== $isOptional) {
                $conflicts++;
            }
            $seenOptionalByKey[$key] = $isOptional;

            $payloadByKey[$key] = [
                'course_id' => $courseId,
                'course_semester' => $semester,
                'discipline_id' => $disciplineId,
                'is_optional' => $isOptional ? 1 : 0,
            ];
        }

        $payload = array_values($payloadByKey);

        $this->info('Resumo antes do upsert:');
        $this->line(" - Linhas totais: " . count($rows));
        $this->line(" - Parse errors/linhas ignoradas: {$parseErrors}");
        $this->line(" - Disciplines criadas: {$createdDisciplines}");
        $this->line(' - Conflitos (is_optional diferente na mesma chave): ' . $conflicts);
        $this->line(' - Itens no curriculum_matrix (payload deduplicado): ' . count($payload));

        if ($dryRun) {
            $this->warn('Dry-run: nenhum dado foi escrito no banco.');
            return 0;
        }

        if ($payload === []) {
            $this->warn('Nada para importar (payload vazio).');
            return 0;
        }

        $this->info('Executando upsert em curriculum_matrix...');

        $chunkSize = 500;
        foreach (array_chunk($payload, $chunkSize) as $chunk) {
            DB::table('curriculum_matrix')->upsert(
                $chunk,
                ['course_id', 'course_semester', 'discipline_id'],
                ['is_optional']
            );
        }

        $this->info('Importação finalizada com sucesso.');

        return 0;
    }

    private function resolveFilePath(string $path): string
    {
        $p = trim($path);
        if ($p === '') {
            return $p;
        }

        if (str_starts_with($p, '/')) {
            return $p;
        }

        return base_path($p);
    }
}

