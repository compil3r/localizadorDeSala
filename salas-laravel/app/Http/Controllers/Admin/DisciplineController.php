<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Discipline;
use Illuminate\Http\Request;

class DisciplineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = Course::when($user->isCoordinator(), fn ($q) => $q->where('coordinator_id', $user->coordinator_id))
            ->orderBy('name')
            ->get();

        $allowedCourseIds = $user->isCoordinator()
            ? $courses->pluck('id')->toArray()
            : null;

        $disciplines = Discipline::with('owningCourse')
            ->when($allowedCourseIds !== null, function ($q) use ($allowedCourseIds) {
                if (empty($allowedCourseIds)) {
                    $q->whereRaw('0 = 1');
                } else {
                    $q->whereIn('owning_course_id', $allowedCourseIds);
                }
            })
            ->orderBy('name')
            ->get();

        return view('admin.disciplines.index', [
            'disciplines' => $disciplines,
            'courses' => $courses,
            'navCurrent' => 'ucs',
        ]);
    }

    public function update(Request $request, Discipline $discipline)
    {
        $user = $request->user();
        if ($user->isCoordinator()) {
            $allowed = Course::where('coordinator_id', $user->coordinator_id)->pluck('id')->toArray();
            if (!in_array((int) $discipline->owning_course_id, $allowed, true)) {
                abort(403, 'Você não tem permissão para alterar esta disciplina.');
            }
        }

        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'owning_course_id' => 'required|integer|exists:courses,id',
        ]);

        if ($user->isCoordinator()) {
            $allowed = Course::where('coordinator_id', $user->coordinator_id)->pluck('id')->toArray();
            if (!in_array((int) $valid['owning_course_id'], $allowed, true)) {
                abort(403, 'Curso não permitido.');
            }
        }

        $discipline->update([
            'name' => $valid['name'],
            'owning_course_id' => $valid['owning_course_id'],
        ]);

        return redirect()->route('admin.disciplines.index')->with('success', 'Disciplina atualizada.');
    }
}
