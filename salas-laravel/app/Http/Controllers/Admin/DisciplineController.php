<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CurriculumMatrix;
use App\Models\Discipline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisciplineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedCourseId = (int) $request->session()->get('current_course_id', 0);

        $courses = Course::when($user->isCoordinator(), fn ($q) => $q->where('coordinator_id', $user->coordinator_id))
            ->orderBy('code')
            ->get();

        $allowedCourseIds = $user->isCoordinator()
            ? $courses->pluck('id')->toArray()
            : null;

        $query = DB::table('curriculum_matrix as cm')
            ->join('courses as c', 'c.id', '=', 'cm.course_id')
            ->join('disciplines as d', 'd.id', '=', 'cm.discipline_id')
            ->leftJoin('courses as mother', 'mother.id', '=', 'd.owning_course_id')
            ->select([
                'cm.id as matrix_id',
                'cm.course_id',
                'c.code as course_code',
                'c.name as course_name',
                'cm.course_semester',
                'd.id as discipline_id',
                'd.name as discipline_name',
                'cm.is_optional',
                'd.owning_course_id as mother_course_id',
                'mother.code as mother_course_code',
                'mother.name as mother_course_name',
            ])
            ->orderBy('c.code')
            ->orderBy('cm.course_semester')
            ->orderBy('d.name');

        if ($allowedCourseIds !== null) {
            if (empty($allowedCourseIds)) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('cm.course_id', $allowedCourseIds);
            }
        }

        if ($selectedCourseId > 0) {
            $query->where('cm.course_id', $selectedCourseId);
        }

        $rows = $query->get()->map(function ($row) {
            $courseId = (int) $row->course_id;
            $motherCourseId = $row->mother_course_id !== null ? (int) $row->mother_course_id : null;

            if ((int) $row->is_optional === 1) {
                $type = 'OPTATIVA';
            } elseif ($motherCourseId !== null && $motherCourseId === $courseId) {
                $type = 'PRÓPRIA';
            } else {
                $type = 'COMPARTILHADA';
            }

            $row->type = $type;
            return $row;
        });

        $matrixByCourse = $rows->groupBy('course_code');

        $optionalRows = $rows
            ->filter(fn ($r) => (int) $r->is_optional === 1)
            ->sortBy(fn ($r) => ($r->course_code ?? '') . '|' . ($r->discipline_name ?? ''));

        return view('admin.disciplines.index', [
            'matrixByCourse' => $matrixByCourse,
            'optionalRows' => $optionalRows,
            'navCurrent' => 'ucs',
        ]);
    }

    public function updateMatrix(Request $request, CurriculumMatrix $curriculumMatrix)
    {
        $user = $request->user();
        $courseId = (int) $curriculumMatrix->course_id;

        if ($user->isCoordinator()) {
            $allowed = Course::where('coordinator_id', $user->coordinator_id)->pluck('id')->toArray();
            if (!in_array($courseId, $allowed, true)) {
                abort(403, 'Você não tem permissão para alterar a matriz deste curso.');
            }
        }

        $valid = $request->validate([
            'course_semester' => 'required|integer|min:1|max:30',
        ]);

        $isOptional = $request->boolean('is_optional');

        $dup = CurriculumMatrix::query()
            ->where('course_id', $courseId)
            ->where('course_semester', (int) $valid['course_semester'])
            ->where('discipline_id', (int) $curriculumMatrix->discipline_id)
            ->where('id', '!=', (int) $curriculumMatrix->id)
            ->exists();

        if ($dup) {
            return redirect()
                ->route('admin.disciplines.index')
                ->with('error', 'Já existe outra linha na matriz para este curso, semestre e disciplina.');
        }

        $curriculumMatrix->update([
            'course_semester' => (int) $valid['course_semester'],
            'is_optional' => $isOptional,
        ]);

        return redirect()->route('admin.disciplines.index')->with('success', 'Item da matriz atualizado.');
    }

    /**
     * Catálogo/CRUD de disciplinas (para edição de owning_course_id).
     */
    public function catalog(Request $request)
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
                    return;
                }

                // Coordenador consegue ver disciplinas "sem mãe" para conseguir preencher owning_course_id depois.
                $q->whereIn('owning_course_id', $allowedCourseIds)
                    ->orWhereNull('owning_course_id');
            })
            ->orderBy('name')
            ->get();

        return view('admin.discipline_catalog.index', [
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
            // Se a disciplina ainda não tem "mãe" definida, o coordenador pode preenchê-la
            // via owning_course_id do formulário (validação abaixo).
            if ($discipline->owning_course_id !== null && !in_array((int) $discipline->owning_course_id, $allowed, true)) {
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
