<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseSwitchController;
use App\Http\Controllers\Admin\OfferingController;
use App\Http\Controllers\Admin\DisciplineController;
use App\Http\Controllers\Admin\CoordinatorController;
use App\Http\Controllers\Admin\FicAreaController;
use App\Http\Controllers\Admin\FicCourseController;
use App\Http\Controllers\Admin\FicSessionController;
use App\Http\Controllers\KioskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KioskController::class, 'index'])->name('kiosk');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'admin'])->prefix('admin/fic')->name('admin.fic.')->group(function () {
    Route::get('/', [FicAreaController::class, 'index'])->name('areas.index');
    Route::get('/areas/create', [FicAreaController::class, 'create'])->name('areas.create');
    Route::post('/areas', [FicAreaController::class, 'store'])->name('areas.store');
    Route::get('/areas/{area}/edit', [FicAreaController::class, 'edit'])->name('areas.edit');
    Route::put('/areas/{area}', [FicAreaController::class, 'update'])->name('areas.update');

    Route::get('/areas/{area}/courses', [FicCourseController::class, 'index'])->name('courses.index');
    Route::get('/areas/{area}/courses/create', [FicCourseController::class, 'create'])->name('courses.create');
    Route::post('/areas/{area}/courses', [FicCourseController::class, 'store'])->name('courses.store');
    Route::get('/areas/{area}/courses/{course}/edit', [FicCourseController::class, 'edit'])->name('courses.edit');
    Route::put('/areas/{area}/courses/{course}', [FicCourseController::class, 'update'])->name('courses.update');
    Route::delete('/areas/{area}/courses/{course}', [FicCourseController::class, 'destroy'])->name('courses.destroy');

    Route::post('/areas/{area}/courses/{course}/sessions', [FicSessionController::class, 'store'])->name('sessions.store');
    Route::put('/areas/{area}/courses/{course}/sessions/{session}', [FicSessionController::class, 'update'])->name('sessions.update');
    Route::delete('/areas/{area}/courses/{course}/sessions/{session}', [FicSessionController::class, 'destroy'])->name('sessions.destroy');
});

Route::middleware(['auth', 'current.course'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/switch-course', [CourseSwitchController::class, 'switch'])->name('switch-course');
    Route::get('/', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}/offerings', [OfferingController::class, 'index'])->name('offerings.index');
    Route::post('/courses/{course}/offerings', [OfferingController::class, 'store'])->name('offerings.store');
    Route::put('/courses/{course}/offerings', [OfferingController::class, 'update'])->name('offerings.update');
    Route::post('/courses/{course}/offerings/delete', [OfferingController::class, 'destroy'])->name('offerings.destroy');
    Route::get('/disciplines', [DisciplineController::class, 'index'])->name('disciplines.index');
    Route::get('/disciplines/catalog', [DisciplineController::class, 'catalog'])->name('disciplines.catalog');
    Route::put('/disciplines/{discipline}', [DisciplineController::class, 'update'])->name('disciplines.update');
    Route::put('/curriculum-matrix/{curriculum_matrix}', [DisciplineController::class, 'updateMatrix'])->name('curriculum-matrix.update');
    Route::middleware('admin')->group(function () {
        Route::get('/coordinators', [CoordinatorController::class, 'index'])->name('coordinators.index');
        Route::post('/coordinators', [CoordinatorController::class, 'store'])->name('coordinators.store');
        Route::post('/coordinators/reset-password', [CoordinatorController::class, 'resetPassword'])->name('coordinators.reset-password');
    });
});
