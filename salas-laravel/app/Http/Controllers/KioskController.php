<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    private const PREFIXO_CURSO = '/^Curso Superior de Tecnologia em\s+/i';

    public function index(Request $request)
    {
        $tz = 'America/Sao_Paulo';
        $now = Carbon::now($tz);
        $h = (int) $now->format('G');
        $w = (int) $now->format('w'); // 0=dom, 1=seg, ..., 6=sab

        $turno = ($h >= 6 && $h < 12) ? 'MANHA' : 'NOITE';
        $dias = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];
        $diaSemana = $dias[$w];

        $cursos = [];
        $meta = [
            'turno' => $turno,
            'dia_semana' => $diaSemana,
            'hora' => $now->format('H:i'),
            'mensagem' => null,
        ];

        if ($diaSemana === 'DOM') {
            $meta['mensagem'] = 'Nenhuma aula aos domingos.';
            return view('kiosk.index', compact('cursos', 'meta'));
        }

        $courseIds = DB::table('course_offerings')
            ->join('offering_slots', 'course_offerings.offering_slot_id', '=', 'offering_slots.id')
            ->where('offering_slots.turno', $turno)
            ->where('offering_slots.dia_semana', $diaSemana)
            ->distinct()
            ->pluck('course_offerings.course_id');

        $courses = Course::with([
            'offerings' => fn ($q) => $q
                ->whereHas('offeringSlot', fn ($sq) => $sq
                    ->where('turno', $turno)
                    ->where('dia_semana', $diaSemana))
                ->with(['offeringSlot.discipline', 'offeringSlot.teacher']),
        ])
            ->whereIn('id', $courseIds)
            ->orderBy('name')
            ->get();

        foreach ($courses as $c) {
            $disciplinas = $c->offerings
                ->sortBy(fn ($o) => $o->offeringSlot->discipline->name ?? '')
                ->values()
                ->map(fn ($o) => [
                    'nome' => $o->offeringSlot->discipline->name ?? '',
                    'sala' => $o->offeringSlot->room ?? '',
                    'docente' => $o->offeringSlot->teacher->name ?? '',
                ])
                ->all();

            $cursos[] = [
                'id' => $c->id,
                'codigoCurto' => $c->code,
                'nome' => $this->shortCourseName($c->name),
                'disciplinas' => $disciplinas,
            ];
        }

        return view('kiosk.index', compact('cursos', 'meta'));
    }

    private function shortCourseName(string $name): string
    {
        $trimmed = preg_replace(self::PREFIXO_CURSO, '', $name);
        return $trimmed !== null ? trim($trimmed) : $name;
    }
}
