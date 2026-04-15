<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BootstrapCurriculumFromOfferingsCommand extends Command
{
    protected $signature = 'matrizes:bootstrap-from-offerings
        {--dry-run : Não grava no banco, apenas gera relatório}
        {--only-missing : Só insere quando ainda não existir a linha em curriculum_matrix}
        {--period-is-active=1 : Valor de periods.is_active para filtrar}';

    protected $description = 'Preenche curriculum_matrix com COMPARTILHADA/OPTATIVA a partir da oferta atual (periods.is_active).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyMissing = (bool) $this->option('only-missing');
        $periodIsActive = (int) ($this->option('period-is-active') ?? 1);

        $reportTs = date('Ymd_His');
        $reportPath = storage_path("app/matrizes/bootstrap_from_offerings_{$reportTs}.csv");
        File::ensureDirectoryExists(dirname($reportPath));

        // Fonte: ofertas do período ativo.
        // Para cada disciplina da oferta, buscamos a "mãe obrigatória" do curso da disciplina:
        // disciplines.owning_course_id -> curriculum_matrix (is_optional=false) -> course_semester.
        $rows = DB::table('course_offerings as co')
            ->join('offering_slots as os', 'co.offering_slot_id', '=', 'os.id')
            ->join('periods as p', 'os.period_id', '=', 'p.id')
            ->join('disciplines as d', 'os.discipline_id', '=', 'd.id')
            ->leftJoin('curriculum_matrix as cm_mother', function ($join) {
                $join->on('cm_mother.course_id', '=', 'd.owning_course_id')
                    ->on('cm_mother.discipline_id', '=', 'os.discipline_id')
                    ->where('cm_mother.is_optional', '=', 0);
            })
            ->where('p.is_active', '=', $periodIsActive)
            ->whereIn('co.origin_type', ['OPTATIVA', 'COMPARTILHADA'])
            ->select([
                'co.course_id as target_course_id',
                'os.discipline_id as discipline_id',
                'd.owning_course_id as mother_course_id',
                'co.origin_type as origin_type',
                'cm_mother.course_semester as mother_course_semester',
            ])
            ->get();

        $processed = 0;
        $skippedMissingMother = 0;
        $skippedMissingMotherSemester = 0;
        $conflicts = 0;

        $seenTargetKeyToOptional = []; // key => bool (desired is_optional)
        $conflictSamples = [];
        $missingMotherSamples = [];
        $missingMotherSemesterSamples = [];

        foreach ($rows as $r) {
            $processed++;

            $targetCourseId = (int) $r->target_course_id;
            $disciplineId = (int) $r->discipline_id;
            $motherCourseId = $r->mother_course_id !== null ? (int) $r->mother_course_id : null;

            if ($motherCourseId === null) {
                $skippedMissingMother++;
                if (count($missingMotherSamples) < 20) {
                    $missingMotherSamples[] = [
                        'target_course_id' => $targetCourseId,
                        'discipline_id' => $disciplineId,
                        'origin_type' => (string) $r->origin_type,
                    ];
                }
                continue;
            }

            $motherSemester = $r->mother_course_semester !== null ? (int) $r->mother_course_semester : null;
            if ($motherSemester === null) {
                $skippedMissingMotherSemester++;
                if (count($missingMotherSemesterSamples) < 20) {
                    $missingMotherSemesterSamples[] = [
                        'target_course_id' => $targetCourseId,
                        'discipline_id' => $disciplineId,
                        'mother_course_id' => $motherCourseId,
                        'origin_type' => (string) $r->origin_type,
                    ];
                }
                continue;
            }

            $desiredIsOptional = ((string) $r->origin_type) === 'OPTATIVA';
            $key = "{$targetCourseId}|{$motherSemester}|{$disciplineId}";

            if (isset($seenTargetKeyToOptional[$key])) {
                $prev = $seenTargetKeyToOptional[$key];
                if ($prev !== $desiredIsOptional) {
                    $conflicts++;
                    $seenTargetKeyToOptional[$key] = $prev || $desiredIsOptional; // OPTATIVA (true) tem prioridade
                    if (count($conflictSamples) < 20) {
                        $conflictSamples[] = [
                            'key' => $key,
                            'prev_optional' => $prev ? 'SIM' : 'NÃO',
                            'new_optional' => $desiredIsOptional ? 'SIM' : 'NÃO',
                        ];
                    }
                }
                continue;
            }

            $seenTargetKeyToOptional[$key] = $desiredIsOptional;
        }

        // Payload deduplicado por (course_id, course_semester, discipline_id)
        $payload = [];
        $courseIds = [];
        $disciplineIds = [];

        foreach ($seenTargetKeyToOptional as $key => $isOptional) {
            [$courseId, $semester, $disciplineId] = explode('|', $key, 3);
            $courseId = (int) $courseId;
            $semester = (int) $semester;
            $disciplineId = (int) $disciplineId;

            $payload[] = [
                'course_id' => $courseId,
                'course_semester' => $semester,
                'discipline_id' => $disciplineId,
                'is_optional' => $isOptional ? 1 : 0,
            ];

            $courseIds[$courseId] = true;
            $disciplineIds[$disciplineId] = true;
        }

        // Pré-carrega existência para contagem e --only-missing.
        $existingMap = [];
        if ($payload !== []) {
            $existingRows = DB::table('curriculum_matrix')
                ->select(['course_id', 'course_semester', 'discipline_id', 'is_optional'])
                ->whereIn('course_id', array_keys($courseIds))
                ->whereIn('discipline_id', array_keys($disciplineIds))
                ->get();

            foreach ($existingRows as $er) {
                $k = ((int) $er->course_id) . '|' . ((int) $er->course_semester) . '|' . ((int) $er->discipline_id);
                $existingMap[$k] = (bool) ((int) $er->is_optional);
            }
        }

        $toUpsert = [];
        $created = 0;
        $updated = 0;
        $skippedAlreadySame = 0;
        $skippedAlreadyExists = 0;

        foreach ($payload as $p) {
            $key = "{$p['course_id']}|{$p['course_semester']}|{$p['discipline_id']}";
            $existsOptional = array_key_exists($key, $existingMap) ? $existingMap[$key] : null;

            if ($onlyMissing) {
                if ($existsOptional !== null) {
                    $skippedAlreadyExists++;
                    continue;
                }
                $created++;
                $toUpsert[] = $p;
                continue;
            }

            if ($existsOptional === null) {
                $created++;
                $toUpsert[] = $p;
                continue;
            }

            $desired = (bool) ((int) $p['is_optional']);
            if ($existsOptional === $desired) {
                $skippedAlreadySame++;
                continue;
            }

            $updated++;
            $toUpsert[] = $p;
        }

        // Escrita do relatório.
        $fh = fopen($reportPath, 'wb');
        fputcsv($fh, [
            'section',
            'metric',
            'value',
        ], ';');

        $summary = [
            ['processed_offering_rows', (string) $processed],
            ['payload_deduped_items', (string) count($payload)],
            ['skipped_missing_mother', (string) $skippedMissingMother],
            ['skipped_missing_mother_semester', (string) $skippedMissingMotherSemester],
            ['conflicts_is_optional', (string) $conflicts],
            ['only_missing', $onlyMissing ? 'SIM' : 'NÃO'],
            ['to_upsert_count', (string) count($toUpsert)],
            ['created', (string) $created],
            ['updated', (string) $updated],
            ['skipped_already_same', (string) $skippedAlreadySame],
            ['skipped_already_exists_only_missing', (string) $skippedAlreadyExists],
        ];

        foreach ($summary as [$metric, $value]) {
            fputcsv($fh, ['summary', $metric, $value], ';');
        }

        fputcsv($fh, ['details', 'missing_mother_samples', ''], ';');
        foreach ($missingMotherSamples as $s) {
            fputcsv($fh, ['missing_mother', json_encode($s, JSON_UNESCAPED_UNICODE), ''], ';');
        }

        fputcsv($fh, ['details', 'missing_mother_semester_samples', ''], ';');
        foreach ($missingMotherSemesterSamples as $s) {
            fputcsv($fh, ['missing_mother_semester', json_encode($s, JSON_UNESCAPED_UNICODE), ''], ';');
        }

        fputcsv($fh, ['details', 'conflict_samples', ''], ';');
        foreach ($conflictSamples as $s) {
            fputcsv($fh, ['conflict', json_encode($s, JSON_UNESCAPED_UNICODE), ''], ';');
        }

        fclose($fh);

        if ($dryRun) {
            $this->warn("Dry-run: nada foi gravado. Relatório em: {$reportPath}");
            $this->table([], [
                ['processed', $processed],
                ['payload', count($payload)],
                ['to_upsert', count($toUpsert)],
            ]);
            return 0;
        }

        if ($toUpsert === []) {
            $this->info("Nada para importar. Relatório em: {$reportPath}");
            return 0;
        }

        $this->info('Executando upsert em curriculum_matrix...');

        $chunkSize = 500;
        foreach (array_chunk($toUpsert, $chunkSize) as $chunk) {
            DB::table('curriculum_matrix')->upsert(
                $chunk,
                ['course_id', 'course_semester', 'discipline_id'],
                ['is_optional']
            );
        }

        $this->info("Importação bootstrap finalizada. Relatório em: {$reportPath}");

        return 0;
    }
}

