<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FicArea;
use App\Models\FicCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FicCourseController extends Controller
{
    public function index(FicArea $area): View
    {
        $courses = $area->courses()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.fic.courses.index', [
            'area' => $area,
            'courses' => $courses,
            'navCurrent' => 'fic',
        ]);
    }

    public function create(FicArea $area): View
    {
        return view('admin.fic.courses.create', [
            'area' => $area,
            'navCurrent' => 'fic',
        ]);
    }

    public function store(Request $request, FicArea $area): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['fic_area_id'] = $area->id;

        FicCourse::query()->create($data);

        return redirect()->route('admin.fic.courses.index', $area)->with('success', 'Curso FIC criado.');
    }

    public function edit(FicArea $area, FicCourse $course): View
    {
        abort_unless((int) $course->fic_area_id === (int) $area->id, 404);
        $course->load(['sessions' => fn ($q) => $q->orderBy('session_date')->orderBy('sort_order')]);

        return view('admin.fic.courses.edit', [
            'area' => $area,
            'course' => $course,
            'navCurrent' => 'fic',
        ]);
    }

    public function update(Request $request, FicArea $area, FicCourse $course): RedirectResponse
    {
        abort_unless((int) $course->fic_area_id === (int) $area->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $course->update($data);

        return redirect()->route('admin.fic.courses.edit', [$area, $course])->with('success', 'Curso atualizado.');
    }

    public function destroy(FicArea $area, FicCourse $course): RedirectResponse
    {
        abort_unless((int) $course->fic_area_id === (int) $area->id, 404);
        $course->delete();

        return redirect()->route('admin.fic.courses.index', $area)->with('success', 'Curso removido.');
    }
}
