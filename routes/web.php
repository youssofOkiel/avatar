<?php

use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\EducationLevelController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => redirect()->route('admin.reservations.index'))->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('education-levels', EducationLevelController::class)->except(['show']);
    Route::resource('subjects', SubjectController::class)->except(['show']);
    Route::resource('teachers', TeacherController::class);
    Route::resource('students', StudentController::class)->except(['show']);

    Route::get('sessions', [ClassSessionController::class, 'index'])->name('sessions.index');
    Route::post('sessions', [ClassSessionController::class, 'store'])->name('sessions.store');
    Route::get('sessions/{classSession}', [ClassSessionController::class, 'show'])->name('sessions.show');
    Route::post('sessions/{classSession}/students', [ClassSessionController::class, 'storeStudent'])->name('sessions.students.store');
    Route::delete('sessions/{classSession}/students/{student}', [ClassSessionController::class, 'destroyStudent'])->name('sessions.students.destroy');
    Route::delete('sessions/{classSession}', [ClassSessionController::class, 'destroy'])->name('sessions.destroy');

    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
});

require __DIR__.'/settings.php';
