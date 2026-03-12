<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\OfferingController;
use App\Http\Controllers\Admin\DisciplineController;
use App\Http\Controllers\Admin\CoordinatorController;
use App\Http\Controllers\KioskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KioskController::class, 'index'])->name('kiosk');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}/offerings', [OfferingController::class, 'index'])->name('offerings.index');
    Route::post('/courses/{course}/offerings', [OfferingController::class, 'store'])->name('offerings.store');
    Route::put('/courses/{course}/offerings', [OfferingController::class, 'update'])->name('offerings.update');
    Route::post('/courses/{course}/offerings/delete', [OfferingController::class, 'destroy'])->name('offerings.destroy');
    Route::get('/disciplines', [DisciplineController::class, 'index'])->name('disciplines.index');
    Route::put('/disciplines/{discipline}', [DisciplineController::class, 'update'])->name('disciplines.update');
    Route::middleware('admin')->group(function () {
        Route::get('/coordinators', [CoordinatorController::class, 'index'])->name('coordinators.index');
        Route::post('/coordinators', [CoordinatorController::class, 'store'])->name('coordinators.store');
        Route::post('/coordinators/reset-password', [CoordinatorController::class, 'resetPassword'])->name('coordinators.reset-password');
    });
});
