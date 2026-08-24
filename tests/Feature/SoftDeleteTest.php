<?php

use App\Models\ClassSession;
use App\Models\EducationLevel;
use App\Models\Expense;
use App\Models\ExternalLecturer;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSchedule;
use App\Models\User;
use App\Services\SessionOccurrenceService;
use Illuminate\Support\Carbon;

test('deletable models use soft deletes', function (string $modelClass) {
    $model = $modelClass::factory()->create();

    $model->delete();

    expect($modelClass::query()->find($model->id))->toBeNull();
    $this->assertSoftDeleted($model);
})->with([
    User::class,
    Student::class,
    Subject::class,
    Teacher::class,
    Expense::class,
    Reservation::class,
    ExternalLecturer::class,
    ClassSession::class,
]);

test('deleting a teacher soft deletes its schedules', function () {
    $admin = User::factory()->admin()->create();
    $teacher = Teacher::factory()->create();
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $teacher->id,
        'education_level_id' => EducationLevel::factory()->create()->id,
        'subject_id' => Subject::factory()->create()->id,
        'day_of_week' => 0,
        'starts_at' => '16:00',
        'ends_at' => '17:00',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.teachers.destroy', $teacher))
        ->assertRedirect(route('admin.teachers.index'));

    $this->assertSoftDeleted($teacher);
    $this->assertSoftDeleted($schedule);
});

test('soft deleted reservation can be restored via firstOrCreate', function () {
    $reservation = Reservation::factory()->create();
    $reservation->delete();

    $restored = Reservation::withTrashed()->firstOrCreate([
        'student_id' => $reservation->student_id,
        'education_level_id' => $reservation->education_level_id,
        'subject_id' => $reservation->subject_id,
        'teacher_id' => $reservation->teacher_id,
    ]);

    if ($restored->trashed()) {
        $restored->restore();
    }

    expect($restored->id)->toBe($reservation->id);
    expect($restored->fresh()->trashed())->toBeFalse();
    expect(Reservation::query()->count())->toBe(1);
});

test('soft deleted class session is restored when occurrence is recreated', function () {
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => Teacher::factory()->create()->id,
        'education_level_id' => EducationLevel::factory()->create()->id,
        'subject_id' => Subject::factory()->create()->id,
        'day_of_week' => 1,
        'starts_at' => '10:00',
        'ends_at' => '11:00',
    ]);

    $service = app(SessionOccurrenceService::class);
    $date = Carbon::parse('next monday')->startOfDay();
    $session = $service->findOrCreateForTeacherSchedule($schedule, $date);
    $session->delete();

    expect(ClassSession::query()->find($session->id))->toBeNull();

    $restored = $service->findOrCreateForTeacherSchedule($schedule, $date);

    expect($restored->id)->toBe($session->id);
    expect($restored->trashed())->toBeFalse();
});
