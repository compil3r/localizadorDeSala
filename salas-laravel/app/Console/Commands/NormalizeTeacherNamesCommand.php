<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Services\TeacherNameFormatter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeTeacherNamesCommand extends Command
{
    protected $signature = 'teachers:normalize-names
        {--dry-run : Apenas exibe as mudanças, sem gravar}';

    protected $description = 'Padroniza os nomes dos docentes: capitalização correta e apenas primeiro + último nome.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $teachers = Teacher::orderBy('name')->get();
        if ($teachers->isEmpty()) {
            $this->warn('Nenhum docente cadastrado.');

            return 0;
        }

        $changes = [];
        foreach ($teachers as $teacher) {
            $novo = TeacherNameFormatter::format((string) $teacher->name);
            if ($novo !== '' && $novo !== $teacher->name) {
                $changes[] = ['id' => $teacher->id, 'de' => $teacher->name, 'para' => $novo];
            }
        }

        if ($changes === []) {
            $this->info('Todos os nomes já estão padronizados. Nada a fazer.');

            return 0;
        }

        $this->table(['ID', 'De', 'Para'], array_map(
            static fn ($c) => [$c['id'], $c['de'], $c['para']],
            $changes
        ));

        if ($dryRun) {
            $this->warn('Dry-run: nenhuma alteração foi gravada. ('.count($changes).' seriam atualizadas)');

            return 0;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                Teacher::where('id', $c['id'])->update(['name' => $c['para']]);
            }
        });

        $this->info(count($changes).' nome(s) de docente padronizado(s) com sucesso.');

        return 0;
    }
}
