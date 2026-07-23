<?php

use App\Models\EducationLevel;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/**
 * Set up a teacher who teaches a subject in a level, with one schedule slot.
 *
 * @return array{level: EducationLevel, subject: Subject, teacher: Teacher, schedule: \App\Models\TeacherSchedule}
 */
function reservationContext(): array
{
    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $subject->educationLevels()->attach($level->id);
    $teacher = Teacher::factory()->create();
    $teacher->teacherSubjects()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
    ]);
    $schedule = $teacher->schedules()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'day_of_week' => 0,
        'starts_at' => '16:00',
        'ends_at' => '17:30',
    ]);

    return compact('level', 'subject', 'teacher', 'schedule');
}

test('admin can view the create reservation page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reservations.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reservations/create')
            ->has('students')
            ->has('levels')
            ->has('teacherSubjects')
            ->has('schedules'));
});

test('admin can create a reservation for a new student with teacher and schedule', function () {
    ['level' => $level, 'subject' => $subject, 'teacher' => $teacher, 'schedule' => $schedule] = reservationContext();

    $this->actingAs($this->admin)
        ->post(route('admin.reservations.store'), [
            'name' => 'عمر حسن',
            'phone' => '01098765432',
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'teacher_schedule_ids' => [$schedule->id],
        ])
        ->assertRedirect(route('admin.reservations.index'));

    $student = Student::query()->first();
    expect($student)->name->toBe('عمر حسن')->phone->toBe('01098765432');

    $reservation = Reservation::query()->first();
    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'student_id' => $student->id,
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);
    $this->assertDatabaseHas('reservation_schedule', [
        'reservation_id' => $reservation->id,
        'teacher_schedule_id' => $schedule->id,
    ]);
});

test('reservation requires a name or phone when no student is chosen', function () {
    ['level' => $level, 'subject' => $subject, 'teacher' => $teacher] = reservationContext();

    $this->actingAs($this->admin)
        ->post(route('admin.reservations.store'), [
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ])
        ->assertSessionHasErrors(['name', 'phone']);
});

test('reservation requires level, subject and teacher', function () {
    $student = Student::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.reservations.store'), [
            'student_id' => $student->id,
        ])
        ->assertSessionHasErrors(['education_level_id', 'subject_id', 'teacher_id']);
});

test('reserving the same student, subject and teacher twice does not duplicate', function () {
    $student = Student::factory()->create();
    ['level' => $level, 'subject' => $subject, 'teacher' => $teacher] = reservationContext();

    $payload = [
        'student_id' => $student->id,
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ];

    $this->actingAs($this->admin)->post(route('admin.reservations.store'), $payload);
    $this->actingAs($this->admin)->post(route('admin.reservations.store'), $payload);

    expect(Reservation::query()->count())->toBe(1);
});

test('admin can delete a reservation', function () {
    $reservation = Reservation::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.reservations.destroy', $reservation))
        ->assertRedirect(route('admin.reservations.index'));

    $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
});

test('guests cannot create admin reservations', function () {
    ['level' => $level, 'subject' => $subject, 'teacher' => $teacher] = reservationContext();

    $this->post(route('admin.reservations.store'), [
        'name' => 'عمر حسن',
        'phone' => '01098765432',
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ])->assertRedirect(route('login'));
});

test('reservation reuses an existing student matched by phone', function () {
    $existing = Student::factory()->create(['phone' => '01055500000', 'name' => 'قديم']);
    ['level' => $level, 'subject' => $subject, 'teacher' => $teacher] = reservationContext();

    $this->actingAs($this->admin)
        ->post(route('admin.reservations.store'), [
            'phone' => '01055500000',
            'education_level_id' => $level->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

    expect(Student::query()->count())->toBe(1);
    $this->assertDatabaseHas('reservations', [
        'student_id' => $existing->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
    ]);
});

test('admin sees reservations listed', function () {
    Reservation::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reservations/index')
            ->has('reservations.data', 1));
});
