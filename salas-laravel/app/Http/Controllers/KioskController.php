<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Period;
use App\Services\Fic\FicKioskPresenter;
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
        $dias = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'];

        // Modo simulação (apenas pré-visualização): ?sim=1&dia=SEG&turno=NOITE&data=2026-08-03
        $simDia = strtoupper(trim((string) $request->query('dia', '')));
        $simTurno = strtoupper(trim((string) $request->query('turno', '')));
        $simData = trim((string) $request->query('data', ''));
        $simulando = $request->query('sim') !== null || $simDia !== '' || $simTurno !== '' || $simData !== '';

        if ($simData !== '') {
            try {
                $now = Carbon::parse($simData, $tz);
            } catch (\Throwable $e) {
                // data inválida: mantém o horário atual
            }
        }

        if (in_array($simDia, $dias, true)) {
            $alvo = (int) array_search($simDia, $dias, true);
            $now = $now->copy()->addDays(($alvo - (int) $now->format('w') + 7) % 7);
        }

        if (in_array($simTurno, ['MANHA', 'NOITE'], true)) {
            $now = $now->copy()->setTime($simTurno === 'MANHA' ? 9 : 19, 0);
        }

        $h = (int) $now->format('G');
        $w = (int) $now->format('w'); // 0=dom, 1=seg, ..., 6=sab

        $turno = ($h >= 6 && $h < 12) ? 'MANHA' : 'NOITE';
        $diaSemana = $dias[$w];

        $cursos = [];
        $meta = [
            'turno' => $turno,
            'dia_semana' => $diaSemana,
            'hora' => $now->format('H:i'),
            'data' => $now->format('Y-m-d'),
            'mensagem' => null,
            'sim' => $simulando,
        ];

        if ($diaSemana === 'DOM') {
            $meta['mensagem'] = 'Nenhuma aula aos domingos.';
            $presenter = new FicKioskPresenter;
            $cursos = $presenter->hubTiles($now, $turno);

            // Start UniSenac (primeiro dia): serve a skin de game. Reverter => 'kiosk.index'.
            return view('kiosk.start', compact('cursos', 'meta'));
        }

        $activePeriodId = Period::where('is_active', true)->value('id');

        if ($activePeriodId === null) {
            $meta['mensagem'] = 'Nenhum período letivo ativo configurado.';
        } else {
            $courseIds = DB::table('course_offerings')
                ->join('offering_slots', 'course_offerings.offering_slot_id', '=', 'offering_slots.id')
                ->where('offering_slots.period_id', $activePeriodId)
                ->where('offering_slots.turno', $turno)
                ->where('offering_slots.dia_semana', $diaSemana)
                ->distinct()
                ->pluck('course_offerings.course_id');

            $courses = Course::with([
                'offerings' => fn ($q) => $q
                    ->whereHas('offeringSlot', fn ($sq) => $sq
                        ->where('period_id', $activePeriodId)
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
        }

        $presenter = new FicKioskPresenter;
        $cursos = array_merge($cursos, $presenter->hubTiles($now, $turno));

        // Start UniSenac (primeiro dia): serve a skin de game. Reverter => 'kiosk.index'.
        return view('kiosk.start', compact('cursos', 'meta'));
    }

    private function shortCourseName(string $name): string
    {
        $trimmed = preg_replace(self::PREFIXO_CURSO, '', $name);

        return $trimmed !== null ? trim($trimmed) : $name;
    }
}
