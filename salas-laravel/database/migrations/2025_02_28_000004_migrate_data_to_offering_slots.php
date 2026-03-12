<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migra dados: course_offerings_legacy -> offering_slots + course_offerings (vínculos).
     * Preserva todos os vínculos curso/turma/origin_type.
     */
    public function up(): void
    {
        if (!Schema::hasTable('course_offerings_legacy')) {
            return;
        }

        $periodId = (int) DB::table('periods')->orderBy('id')->value('id');
        if ($periodId <= 0) {
            throw new \RuntimeException('Nenhum período encontrado. Execute a migration de periods primeiro.');
        }

        $rows = DB::table('course_offerings_legacy')->get();
        $slotKeyToId = [];

        foreach ($rows as $row) {
            $key = $row->discipline_id . '|' . $row->teacher_id . '|' . $row->turno . '|' . $row->dia_semana . '|' . ($row->room ?? '') . '|' . ($row->observation ?? '');
            if (isset($slotKeyToId[$key])) {
                continue;
            }
            $id = DB::table('offering_slots')->insertGetId([
                'period_id' => $periodId,
                'discipline_id' => $row->discipline_id,
                'teacher_id' => $row->teacher_id,
                'turno' => $row->turno,
                'dia_semana' => $row->dia_semana,
                'room' => $row->room ?: null,
                'observation' => $row->observation ?: null,
            ]);
            $slotKeyToId[$key] = $id;
        }

        $links = [];
        foreach ($rows as $row) {
            $key = $row->discipline_id . '|' . $row->teacher_id . '|' . $row->turno . '|' . $row->dia_semana . '|' . ($row->room ?? '') . '|' . ($row->observation ?? '');
            $slotId = $slotKeyToId[$key];
            $linkKey = $row->course_id . '|' . $slotId;
            if (isset($links[$linkKey])) {
                if ($row->origin_type === 'PROPRIA') {
                    $links[$linkKey] = $row->origin_type;
                }
                continue;
            }
            $links[$linkKey] = $row->origin_type;
        }
        foreach ($links as $linkKey => $originType) {
            [$courseId, $slotId] = explode('|', $linkKey, 2);
            DB::table('course_offerings')->insert([
                'course_id' => (int) $courseId,
                'offering_slot_id' => (int) $slotId,
                'origin_type' => $originType,
            ]);
        }

        Schema::dropIfExists('course_offerings_legacy');
    }

    public function down(): void
    {
        // Reversão não trivial (exigiria recriar legacy a partir de slots + vínculos). Deixar vazio ou implementar se necessário.
    }
};
