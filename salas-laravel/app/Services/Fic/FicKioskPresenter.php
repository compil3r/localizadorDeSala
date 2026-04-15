<?php

namespace App\Services\Fic;

use App\Models\FicArea;
use App\Models\FicCourse;
use App\Models\FicSession;
use Carbon\Carbon;

class FicKioskPresenter
{
    /**
     * Cartões de área FIC só entram se houver pelo menos um encontro hoje
     * que se sobreponha ao turno do totem (mesma lógica MANHA/NOITE da graduação).
     *
     * @return list<array<string, mixed>>
     */
    public function hubTiles(Carbon $now, string $turno): array
    {
        $todayStr = $now->toDateString();

        $areas = FicArea::query()
            ->where('kiosk_after_graduation', true)
            ->orderBy('sort_order')
            ->with([
                'courses' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'sessions' => fn ($sq) => $sq
                            ->whereDate('session_date', $todayStr)
                            ->orderBy('session_date')
                            ->orderBy('sort_order'),
                    ]),
            ])
            ->get();

        $tiles = [];
        foreach ($areas as $area) {
            $disciplinas = $this->flattenAreaSessions($area->courses, $todayStr, $turno);
            if ($disciplinas === []) {
                continue;
            }

            $tiles[] = [
                'type' => 'fic_hub',
                'id' => 'fic_area_'.$area->id,
                'codigoCurto' => 'FIC',
                'nome' => $area->name,
                'disciplinas' => $disciplinas,
            ];
        }

        return $tiles;
    }

    /**
     * @param  iterable<FicCourse>  $courses
     * @return list<array{nome: string, sala: string, docente: string}>
     */
    private function flattenAreaSessions(iterable $courses, string $todayStr, string $turno): array
    {
        $rows = [];
        foreach ($courses as $fc) {
            foreach ($fc->sessions as $session) {
                if ($session->session_date->toDateString() !== $todayStr) {
                    continue;
                }
                if (! $this->sessionOverlapsKioskTurno($session, $turno)) {
                    continue;
                }
                $rows[] = $this->kioskRowFromSession($fc, $session);
            }
        }

        usort($rows, function (array $a, array $b): int {
            $cmp = strcmp($a['_sort_date'], $b['_sort_date']);
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = ($a['_sort_order'] <=> $b['_sort_order']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['nome'], $b['nome']);
        });

        return array_map(static function (array $r): array {
            unset($r['_sort_date'], $r['_sort_order']);

            return $r;
        }, $rows);
    }

    /**
     * MANHA: [06:00, 12:00) — igual ao critério de hora do totem da graduação.
     * NOITE: restante do dia civil [00:00, 06:00) ∪ [12:00, 24:00).
     */
    private function sessionOverlapsKioskTurno(FicSession $session, string $turno): bool
    {
        $s = $this->timeToMinutes($session->starts_at);
        $e = $this->timeToMinutes($session->ends_at);

        if ($s === null && $e === null) {
            return true;
        }

        if ($s === null) {
            $s = 0;
        }
        if ($e === null) {
            $e = 24 * 60 - 1;
        }
        if ($e <= $s) {
            $e = min($s + 180, 24 * 60 - 1);
        }

        $manhaStart = 6 * 60;
        $manhaEnd = 12 * 60;

        if ($turno === 'MANHA') {
            return $this->intervalsOverlap($s, $e, $manhaStart, $manhaEnd);
        }

        return $this->intervalsOverlap($s, $e, 0, $manhaStart)
            || $this->intervalsOverlap($s, $e, $manhaEnd, 24 * 60);
    }

    private function intervalsOverlap(int $s1, int $e1, int $s2, int $e2): bool
    {
        return max($s1, $s2) < min($e1, $e2);
    }

    private function timeToMinutes(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return (int) $value->format('H') * 60 + (int) $value->format('i');
        }
        $str = (string) $value;
        if (strlen($str) >= 5) {
            $h = (int) substr($str, 0, 2);
            $m = (int) substr($str, 3, 2);

            return $h * 60 + $m;
        }

        return null;
    }

    /**
     * @return array{nome: string, sala: string, docente: string, _sort_date: string, _sort_order: int}
     */
    private function kioskRowFromSession(FicCourse $course, FicSession $session): array
    {
        return [
            'nome' => $course->name,
            'sala' => (string) ($session->room ?? ''),
            'docente' => (string) ($session->docente ?? ''),
            '_sort_date' => $session->session_date->format('Y-m-d'),
            '_sort_order' => (int) $session->sort_order,
        ];
    }
}
