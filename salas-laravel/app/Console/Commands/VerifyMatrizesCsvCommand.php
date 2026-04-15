<?php

namespace App\Console\Commands;

use App\Services\MatrizesCsv\MatrizesCsvMapper;
use App\Services\MatrizesCsv\MatrizesCsvParser;
use App\Services\MatrizesCsv\NameNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyMatrizesCsvCommand extends Command
{
    protected $signature = 'matrizes:verify {--file=../matrizes.csv} {--output=}';
    protected $description = 'Verifica casamento do matrizes.csv com courses/disciplines e gera relatório.';

    public function handle(): int
    {
        $fileOption = (string) $this->option('file');
        $filePath = $this->resolveFilePath($fileOption);

        $outputOption = (string) $this->option('output');
        $outPath = $outputOption !== ''
            ? $this->resolveFilePath($outputOption)
            : $this->defaultOutputPath();

        $rows = MatrizesCsvParser::parse($filePath);
        $maps = MatrizesCsvMapper::buildMaps();

        $courseByCode = $maps['courseByCode'];
        $disciplineByNameNorm = $maps['disciplineByNameNorm'];

        $dir = dirname($outPath);
        File::ensureDirectoryExists($dir);

        $fh = fopen($outPath, 'wb');
        if (!$fh) {
            $this->error("Não foi possível criar o arquivo de saída: {$outPath}");
            return 1;
        }

        fputcsv($fh, [
            'line_no',
            'course_code',
            'course_id',
            'course_semester',
            'discipline_name',
            'discipline_id',
            'is_optional',
            'matched_course',
            'matched_discipline',
            'action',
            'conflict',
        ], ';');

        $seenOptional = [];
        $counts = [
            'total' => 0,
            'parse_error' => 0,
            'missing_course' => 0,
            'missing_discipline' => 0,
            'conflicts' => 0,
            'ok' => 0,
        ];

        foreach ($rows as $row) {
            $counts['total']++;
            $lineNo = (int) ($row['line_no'] ?? 0);

            $courseCodeRaw = (string) ($row['course_code'] ?? '');
            $courseCode = NameNormalizer::normalizeCourseCode($courseCodeRaw);
            $semester = (int) ($row['course_semester'] ?? -1);

            $disciplineNameRaw = (string) ($row['discipline_name'] ?? '');
            $disciplineNorm = NameNormalizer::normalize($disciplineNameRaw);
            $isOptional = (bool) ($row['is_optional'] ?? false);

            $parseError = !empty($row['parse_error'] ?? false);
            if ($parseError || $semester <= 0) {
                $counts['parse_error']++;
                fputcsv($fh, [
                    $lineNo,
                    $courseCodeRaw,
                    '',
                    $semester,
                    $disciplineNameRaw,
                    '',
                    $isOptional ? 'SIM' : 'NÃO',
                    'NO',
                    'NO',
                    'parse_error',
                    '',
                ], ';');
                continue;
            }

            $courseId = $courseByCode[$courseCode] ?? null;
            $disciplineId = $disciplineByNameNorm[$disciplineNorm] ?? null;

            $matchedCourse = $courseId !== null ? 'YES' : 'NO';
            $matchedDiscipline = $disciplineId !== null ? 'YES' : 'NO';

            $action = 'upsert_ok';
            $conflict = '';

            if ($courseId === null) {
                $counts['missing_course']++;
                $action = 'missing_course_error';
            } elseif ($disciplineId === null) {
                $counts['missing_discipline']++;
                $action = 'create_discipline_without_mother';
            } else {
                $key = "{$courseId}|{$semester}|{$disciplineId}";
                if (isset($seenOptional[$key]) && $seenOptional[$key] !== $isOptional) {
                    $counts['conflicts']++;
                    $action = 'conflict_is_optional_update';
                    $conflict = 'is_optional_diff';
                } else {
                    $seenOptional[$key] = $isOptional;
                }
            }

            if ($action === 'upsert_ok') {
                $counts['ok']++;
            }

            fputcsv($fh, [
                $lineNo,
                $courseCodeRaw,
                $courseId ?? '',
                $semester,
                $disciplineNameRaw,
                $disciplineId ?? '',
                $isOptional ? 'SIM' : 'NÃO',
                $matchedCourse,
                $matchedDiscipline,
                $action,
                $conflict,
            ], ';');
        }

        fclose($fh);

        $this->info("Relatório gerado em: {$outPath}");
        $this->table([], [
            ['total', (string) $counts['total']],
            ['parse_error', (string) $counts['parse_error']],
            ['missing_course', (string) $counts['missing_course']],
            ['missing_discipline', (string) $counts['missing_discipline']],
            ['conflicts', (string) $counts['conflicts']],
            ['ok', (string) $counts['ok']],
        ]);

        return 0;
    }

    private function resolveFilePath(string $path): string
    {
        $p = trim($path);
        if ($p === '') {
            return $p;
        }

        // Se for absoluto, use direto. Se for relativo, resolve a partir da base do Laravel.
        if (str_starts_with($p, '/')) {
            return $p;
        }

        return base_path($p);
    }

    private function defaultOutputPath(): string
    {
        $ts = date('Ymd_His');
        return storage_path("app/matrizes/verify_matrizes_{$ts}.csv");
    }
}

