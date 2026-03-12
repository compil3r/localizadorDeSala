<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = Course::with('coordinator')
            ->when($user->isCoordinator(), fn ($q) => $q->where('coordinator_id', $user->coordinator_id))
            ->orderBy('name')
            ->get();

        return view('admin.courses.index', [
            'courses' => $courses,
            'navCurrent' => 'oferta',
        ]);
    }
}
