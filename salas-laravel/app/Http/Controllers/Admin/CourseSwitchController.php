<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseSwitchController extends Controller
{
    public function switch(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $valid = $request->validate([
            'course_id' => 'required|integer|min:1',
            'redirect_route_name' => 'nullable|string',
        ]);

        $courseId = (int) $valid['course_id'];

        $allowedQuery = Course::query()->orderBy('code');
        if ($user->isCoordinator()) {
            $allowedQuery->where('coordinator_id', $user->coordinator_id);
        }

        $allowedIds = $allowedQuery->pluck('id')->all();
        if (!in_array($courseId, $allowedIds, true)) {
            abort(403, 'Você não tem permissão para acessar este curso.');
        }

        $request->session()->put('current_course_id', $courseId);

        $routeName = $valid['redirect_route_name'] ?? '';

        // Mantém o usuário na mesma "área", só trocando o curso quando a rota suporta.
        if ($routeName === 'admin.offerings.index') {
            return redirect()->route('admin.offerings.index', ['course' => $courseId]);
        }

        if ($routeName === 'admin.courses.index') {
            return redirect()->route('admin.courses.index');
        }

        if ($routeName === 'admin.disciplines.index') {
            return redirect()->route('admin.disciplines.index');
        }

        if ($routeName === 'admin.disciplines.catalog') {
            return redirect()->route('admin.disciplines.catalog');
        }

        return redirect()->back();
    }
}

