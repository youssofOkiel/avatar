<?php

use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExternalLecturerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');

    Route::resource('subjects', SubjectController::class)->except(['show']);
    Route::resource('teachers', TeacherController::class);
    Route::resource('external-lecturers', ExternalLecturerController::class);
    Route::resource('students', StudentController::class)->except(['show']);
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy']);

    Route::get('sessions', [ClassSessionController::class, 'index'])->name('sessions.index');
    Route::get('sessions/outcome', [ClassSessionController::class, 'resolveOutcome'])->name('sessions.outcome');
    Route::post('sessions/occurrence/cancel', [ClassSessionController::class, 'cancelOccurrence'])->name('sessions.occurrence.cancel');
    Route::post('sessions/occurrence/restore', [ClassSessionController::class, 'restoreOccurrence'])->name('sessions.occurrence.restore');
    Route::post('sessions', [ClassSessionController::class, 'store'])->name('sessions.store');
    Route::get('sessions/{classSession}', [ClassSessionController::class, 'show'])->name('sessions.show');
    Route::patch('sessions/{classSession}', [ClassSessionController::class, 'update'])->name('sessions.update');
    Route::post('sessions/{classSession}/cancel', [ClassSessionController::class, 'cancel'])->name('sessions.cancel');
    Route::post('sessions/{classSession}/restore', [ClassSessionController::class, 'restore'])->name('sessions.restore');
    Route::post('sessions/{classSession}/students', [ClassSessionController::class, 'storeStudent'])->name('sessions.students.store');
    Route::delete('sessions/{classSession}/students/{student}', [ClassSessionController::class, 'destroyStudent'])->name('sessions.students.destroy');
    Route::delete('sessions/{classSession}', [ClassSessionController::class, 'destroy'])->name('sessions.destroy');

    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');

    Route::middleware('superadmin')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    });
});

require __DIR__.'/settings.php';
