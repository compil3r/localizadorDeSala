<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\OfferingSlot;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfferingController extends Controller
{
    public function index(Course $course, Request $request)
    {
        $this->authorizeCourse($course, $request);

        $offerings = CourseOffering::with(['offeringSlot.discipline', 'offeringSlot.teacher'])
            ->where('course_id', $course->id)
            ->get()
            ->map(function (CourseOffering $o) use ($course) {
                $slot = $o->offeringSlot;
                $otherCount = CourseOffering::where('offering_slot_id', $slot->id)
                    ->where('course_id', '!=', $course->id)
                    ->count();
                return [
                    'id' => $o->id,
                    'offering_slot_id' => $slot->id,
                    'discipline_id' => $slot->discipline_id,
                    'teacher_id' => $slot->teacher_id,
                    'turno' => $slot->turno,
                    'dia_semana' => $slot->dia_semana,
                    'disciplina' => $slot->discipline->name ?? '',
                    'professor' => $slot->teacher->name ?? '',
                    'sala' => $slot->room,
                    'origin_type' => $o->origin_type,
                    'other_courses_count' => $otherCount,
                ];
            });

        $teachers = Teacher::orderBy('name')->get();

        $slotIdsAlreadyInCourse = $offerings->pluck('offering_slot_id')->unique()->values()->all();
        $slotIdsOtherPropria = CourseOffering::where('course_id', '!=', $course->id)
            ->where('origin_type', 'PROPRIA')
            ->pluck('offering_slot_id')
            ->unique()
            ->values();
        $slotIdsToOffer = $slotIdsOtherPropria->diff($slotIdsAlreadyInCourse);

        $allOfferings = OfferingSlot::with(['courseOfferings.course', 'discipline', 'teacher'])
            ->whereIn('id', $slotIdsToOffer)
            ->get()
            ->map(function (OfferingSlot $slot) {
                $firstLink = $slot->courseOfferings->first();
                $c = $firstLink ? $firstLink->course : null;
                return [
                    'offering_slot_id' => $slot->id,
                    'course_code' => $c->code ?? '',
                    'course_name' => $c->name ?? '',
                    'discipline_name' => $slot->discipline->name ?? '',
                    'turno' => $slot->turno,
                    'professor_name' => $slot->teacher->name ?? '',
                    'dia_semana' => $slot->dia_semana,
                    'room' => $slot->room,
                ];
            })
            ->sortBy(fn ($o) => ($o['course_code'] ?? '') . '|' . ($o['discipline_name'] ?? '') . '|' . ($o['turno'] ?? '') . '|' . ($o['professor_name'] ?? ''))
            ->values()
            ->all();

        return view('admin.offerings.index', [
            'course' => $course,
            'offerings' => $offerings,
            'teachers' => $teachers,
            'allOfferings' => $allOfferings,
            'navCurrent' => 'oferta',
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->authorizeCourse($course, $request);

        $valid = $request->validate([
            'offering_slot_id' => 'required|integer|min:1',
            'origin_type' => ['required', Rule::in(['COMPARTILHADA', 'OPTATIVA'])],
        ]);

        $slot = OfferingSlot::findOrFail($valid['offering_slot_id']);

        $exists = CourseOffering::where('course_id', $course->id)
            ->where('offering_slot_id', $slot->id)
            ->exists();

        if ($exists) {
            return redirect()->route('admin.offerings.index', $course)
                ->with('error', 'Esta oferta já está incluída neste curso (mesma turma).');
        }

        CourseOffering::create([
            'course_id' => $course->id,
            'offering_slot_id' => $slot->id,
            'origin_type' => $valid['origin_type'],
        ]);

        return redirect()->route('admin.offerings.index', $course);
    }

    public function update(Request $request, Course $course)
    {
        $this->authorizeCourse($course, $request);

        $valid = $request->validate([
            'offering_id' => 'required|integer|min:1',
            'teacher_id' => 'required|integer|min:1',
            'turno' => ['required', Rule::in(['MANHA', 'NOITE'])],
            'dia_semana' => ['required', Rule::in(['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB'])],
            'room' => 'nullable|string|max:32',
        ]);

        $offering = CourseOffering::with('offeringSlot')
            ->where('id', $valid['offering_id'])
            ->where('course_id', $course->id)
            ->where('origin_type', 'PROPRIA')
            ->firstOrFail();

        $slot = $offering->offeringSlot;

        $dup = CourseOffering::where('course_id', $course->id)
            ->whereHas('offeringSlot', function ($q) use ($valid) {
                $q->where('teacher_id', $valid['teacher_id'])
                    ->where('turno', $valid['turno'])
                    ->where('dia_semana', $valid['dia_semana']);
            })
            ->where('id', '!=', $offering->id)
            ->exists();

        if ($dup) {
            return redirect()->route('admin.offerings.index', $course)
                ->with('error', 'Já existe outra oferta neste curso com esse docente, turno e dia.');
        }

        $slot->update([
            'teacher_id' => $valid['teacher_id'],
            'turno' => $valid['turno'],
            'dia_semana' => $valid['dia_semana'],
            'room' => $valid['room'] ?: null,
        ]);

        return redirect()->route('admin.offerings.index', $course);
    }

    public function destroy(Request $request, Course $course)
    {
        $this->authorizeCourse($course, $request);

        $valid = $request->validate([
            'offering_id' => 'required|integer|min:1',
            'delete_scope' => ['nullable', Rule::in(['current', 'all'])],
        ]);

        $offering = CourseOffering::with('offeringSlot')
            ->where('id', $valid['offering_id'])
            ->where('course_id', $course->id)
            ->firstOrFail();

        if (in_array($offering->origin_type, ['COMPARTILHADA', 'OPTATIVA'], true)) {
            $offering->delete();
        } elseif (($valid['delete_scope'] ?? '') === 'all') {
            CourseOffering::where('offering_slot_id', $offering->offering_slot_id)->delete();
            $offering->offeringSlot->delete();
        } else {
            $offering->delete();
        }

        return redirect()->route('admin.offerings.index', $course);
    }

    private function authorizeCourse(Course $course, Request $request): void
    {
        $user = $request->user();
        if ($user->isCoordinator() && (int) $course->coordinator_id !== (int) $user->coordinator_id) {
            abort(403, 'Você não tem permissão para gerenciar este curso.');
        }
    }
}
