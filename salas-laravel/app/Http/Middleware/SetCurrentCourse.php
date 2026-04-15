<?php

namespace App\Http\Middleware;

use App\Models\Course;
use Closure;
use Illuminate\Http\Request;

class SetCurrentCourse
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $allowedCoursesQuery = Course::query()
            ->orderBy('code');

        if ($user->isCoordinator()) {
            $allowedCoursesQuery->where('coordinator_id', $user->coordinator_id);
        }

        $allowedCourses = $allowedCoursesQuery->get();
        if ($allowedCourses->isEmpty()) {
            return $next($request);
        }

        $currentCourseId = (int) $request->session()->get('current_course_id', 0);

        $ids = $allowedCourses->pluck('id')->all();
        if ($currentCourseId <= 0 || !in_array($currentCourseId, $ids, true)) {
            $currentCourseId = (int) $allowedCourses->first()->id;
            $request->session()->put('current_course_id', $currentCourseId);
        }

        $request->attributes->set('allowedCourses', $allowedCourses);
        $request->attributes->set('currentCourse', $allowedCourses->firstWhere('id', $currentCourseId));

        return $next($request);
    }
}

