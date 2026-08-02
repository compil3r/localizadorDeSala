<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Discipline;
use App\Models\OfferingSlot;
use App\Models\Period;
use App\Models\Teacher;
use App\Services\MatrizesCsv\NameNormalizer;
use App\Services\OfertaCsv\OfertaCsvParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOfertaCsvCommand extends Command
{
    protected $signature = 'oferta:import
        {--file=../oferta-2026-2.csv}
        {--period=2026/2}
        {--dry-run}
        {--activate : Desativa os demais períodos e ativa o período importado}';

    protected $description = 'Importa oferta.csv (turma;curso_prefixo;disciplina;turno;dia_semana;sala;professor) para offering_slots/course_offerings.';

    /** Prefixo de TURMA na planilha -> código de courses.code */
    private const PREFIX_TO_COURSE_CODE = [
        'ADS' => 'ADS',
        'CC' => 'CC',
        'DM' => 'MODA',
        'IA' => 'IA',
        'MK' => 'MKT',
        'PG' => 'PG',
        'PM' => 'PMM',
        'RD' => 'REDES',
        'SEG' => 'SEG',
    ];

    public function handle(): int
    {
        $filePath = $this->resolveFilePath((string) $this->option('file'));
        $periodName = trim((string) $this->option('period'));
        $dryRun = (bool) $this->option('dry-run');
        $activate = (bool) $this->option('activate');

        if ($periodName === '') {
            $this->error('Informe --period=AAAA/N (ex.: 2026/2).');
            return 1;
        }

        $rows = OfertaCsvParser::parse($filePath);
        if ($rows === []) {
            $this->warn('Nenhuma linha válida no CSV.');
            return 0;
        }

        // Pré-validação: todo prefixo precisa estar mapeado e o curso precisa existir.
        $missingCourses = [];
        $seenPrefixes = [];
        foreach ($rows as $row) {
            $prefix = $row['curso_prefixo'];
            if (isset($seenPrefixes[$prefix])) {
                continue;
            }
            $seenPrefixes[$prefix] = true;

            $code = self::PREFIX_TO_COURSE_CODE[$prefix] ?? null;
            if ($code === null) {
                $missingCourses[$prefix] = 'prefixo sem mapeamento em PREFIX_TO_COURSE_CODE';
                continue;
            }
            if (!Course::where('code', $code)->exists()) {
                $missingCourses[$prefix] = "curso com code={$code} não existe";
            }
        }

        if ($missingCourses !== []) {
            $this->error('Falha: existem prefixos de TURMA sem curso correspondente no banco.');
            foreach ($missingCourses as $prefix => $reason) {
                $this->line(" - {$prefix}: {$reason}");
            }
            return 1;
        }

        $courseIdByCode = Course::pluck('id', 'code')->all();

        $teacherByNorm = Teacher::pluck('id', 'name')->mapWithKeys(
            fn ($id, $name) => [NameNormalizer::normalize((string) $name) => $id]
        )->all();

        $disciplineByNorm = Discipline::pluck('id', 'name')->mapWithKeys(
            fn ($id, $name) => [NameNormalizer::normalize((string) $name) => $id]
        )->all();

        $stats = [
            'linhas' => count($rows),
            'professores_criados' => 0,
            'disciplinas_criadas' => 0,
            'offering_slots_criados' => 0,
            'offering_slots_existentes' => 0,
            'course_offerings_criados' => 0,
            'course_offerings_existentes' => 0,
        ];

        DB::beginTransaction();

        try {
            $period = Period::firstOrCreate(
                ['name' => $periodName],
                ['slug' => str_replace('/', '-', $periodName), 'is_active' => false]
            );

            foreach ($rows as $row) {
                $courseCode = self::PREFIX_TO_COURSE_CODE[$row['curso_prefixo']];
                $courseId = $courseIdByCode[$courseCode];

                $teacherNorm = NameNormalizer::normalize($row['professor']);
                if (!isset($teacherByNorm[$teacherNorm])) {
                    $teacher = Teacher::create(['name' => $row['professor']]);
                    $teacherByNorm[$teacherNorm] = $teacher->id;
                    $stats['professores_criados']++;
                }
                $teacherId = $teacherByNorm[$teacherNorm];

                $disciplineNorm = NameNormalizer::normalize($row['disciplina']);
                if (!isset($disciplineByNorm[$disciplineNorm])) {
                    $discipline = Discipline::create([
                        'name' => $row['disciplina'],
                        'owning_course_id' => $courseId,
                    ]);
                    $disciplineByNorm[$disciplineNorm] = $discipline->id;
                    $stats['disciplinas_criadas']++;
                }
                $disciplineId = $disciplineByNorm[$disciplineNorm];

                $turno = $row['turno'];
                $diaSemana = $row['dia_semana'];

                if (!in_array($turno, ['MANHA', 'NOITE'], true)) {
                    $this->warn("Linha {$row['line_no']}: turno inválido '{$turno}', ignorando.");
                    continue;
                }
                if (!in_array($diaSemana, ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'], true)) {
                    $this->warn("Linha {$row['line_no']}: dia_semana inválido '{$diaSemana}', ignorando.");
                    continue;
                }

                $slot = OfferingSlot::where('period_id', $period->id)
                    ->where('discipline_id', $disciplineId)
                    ->where('teacher_id', $teacherId)
                    ->where('turno', $turno)
                    ->where('dia_semana', $diaSemana)
                    ->first();

                if ($slot === null) {
                    $slot = OfferingSlot::create([
                        'period_id' => $period->id,
                        'discipline_id' => $disciplineId,
                        'teacher_id' => $teacherId,
                        'turno' => $turno,
                        'dia_semana' => $diaSemana,
                        'room' => $row['sala'] !== '' ? $row['sala'] : null,
                    ]);
                    $stats['offering_slots_criados']++;
                } else {
                    $stats['offering_slots_existentes']++;
                    if ($row['sala'] !== '' && $slot->room !== $row['sala']) {
                        $slot->update(['room' => $row['sala']]);
                    }
                }

                $offeringExists = CourseOffering::where('course_id', $courseId)
                    ->where('offering_slot_id', $slot->id)
                    ->exists();

                if (!$offeringExists) {
                    CourseOffering::create([
                        'course_id' => $courseId,
                        'offering_slot_id' => $slot->id,
                        'origin_type' => 'PROPRIA',
                    ]);
                    $stats['course_offerings_criados']++;
                } else {
                    $stats['course_offerings_existentes']++;
                }
            }

            if ($activate) {
                Period::where('id', '!=', $period->id)->update(['is_active' => false]);
                $period->update(['is_active' => true]);
            }

            $this->info('Resumo da importação:');
            foreach ($stats as $k => $v) {
                $this->line(" - {$k}: {$v}");
            }
            $this->line(' - período: ' . $period->name . ' (id=' . $period->id . ', is_active=' . ($period->fresh()->is_active ? 'sim' : 'não') . ')');

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry-run: nenhuma alteração foi persistida.');
                return 0;
            }

            DB::commit();
            $this->info('Importação finalizada com sucesso.');
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Erro durante a importação, nada foi persistido: ' . $e->getMessage());
            return 1;
        }
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
