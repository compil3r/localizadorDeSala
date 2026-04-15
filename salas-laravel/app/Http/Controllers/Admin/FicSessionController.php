<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FicArea;
use App\Models\FicCourse;
use App\Models\FicSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FicSessionController extends Controller
{
    public function store(Request $request, FicArea $area, FicCourse $course): RedirectResponse
    {
        abort_unless((int) $course->fic_area_id === (int) $area->id, 404);
        $this->normalizeTimeFields($request);
        $data = $this->validatedSession($request);
        $data['fic_course_id'] = $course->id;
        FicSession::query()->create($data);

        return redirect()->route('admin.fic.courses.edit', [$area, $course])->with('success', 'Encontro adicionado ao cronograma.');
    }

    public function update(Request $request, FicArea $area, FicCourse $course, FicSession $session): RedirectResponse
    {
        abort_unless((int) $course->fic_area_id === (int) $area->id, 404);
        abort_unless((int) $session->fic_course_id === (int) $course->id, 404);
        $this->normalizeTimeFields($request);
        $session->update($this->validatedSession($request));

        return redirect()->route('admin.fic.courses.edit', [$area, $course])->with('success', 'Encontro atualizado.');
    }

    public function destroy(FicArea $area, FicCourse $course, FicSession $session): RedirectResponse
    {
        abort_unless((int) $course->fic_area_id === (int) $area->id, 404);
        abort_unless((int) $session->fic_course_id === (int) $course->id, 404);
        $session->delete();

        return redirect()->route('admin.fic.courses.edit', [$area, $course])->with('success', 'Encontro removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSession(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'session_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'room' => ['nullable', 'string', 'max:64'],
            'docente' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['label'] = isset($data['label']) && $data['label'] !== '' ? $data['label'] : null;
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;
        $data['room'] = $data['room'] ?? null;
        $data['docente'] = $data['docente'] ?? null;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function normalizeTimeFields(Request $request): void
    {
        $request->merge([
            'starts_at' => $request->filled('starts_at') ? $request->input('starts_at') : null,
            'ends_at' => $request->filled('ends_at') ? $request->input('ends_at') : null,
        ]);
    }
}
