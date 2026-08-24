<?php

use App\Models\ClassSession;
use App\Models\EducationLevel;
use App\Models\EducationLevelGroup;
use App\Models\ExternalLecturer;
use App\Models\ExternalLecturerSchedule;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSchedule;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->room = Room::factory()->create();
    $this->level = EducationLevel::factory()->create();
    $this->subject = Subject::factory()->create();
    $this->teacher = Teacher::factory()->create();
});

test('resolving teacher schedule outcome creates session with reservation students', function () {
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $this->teacher->id,
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '16:00:00',
        'ends_at' => '17:30:00',
    ]);

    $student = Student::factory()->create();
    $reservation = Reservation::factory()->create([
        'student_id' => $student->id,
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'teacher_id' => $this->teacher->id,
    ]);
    $reservation->teacherSchedules()->attach($schedule->id);

    $date = '2026-08-16';

    $this->actingAs($this->admin)
        ->get(route('admin.sessions.outcome', [
            'kind' => 'teacher',
            'id' => $schedule->id,
            'date' => $date,
        ]))
        ->assertRedirect();

    $session = ClassSession::query()->first();

    expect($session)->not->toBeNull();
    expect($session->teacher_schedule_id)->toBe($schedule->id);
    expect($session->starts_at->toDateString())->toBe($date);
    expect($session->students)->toHaveCount(1);
    expect($session->students->first()->id)->toBe($student->id);

    $this->actingAs($this->admin)
        ->get(route('admin.sessions.outcome', [
            'kind' => 'teacher',
            'id' => $schedule->id,
            'date' => $date,
        ]))
        ->assertRedirect(route('admin.sessions.show', $session));

    expect(ClassSession::query()->count())->toBe(1);
});

test('resolving external schedule outcome creates external session', function () {
    $lecturer = ExternalLecturer::factory()->create();
    $schedule = ExternalLecturerSchedule::factory()->create([
        'external_lecturer_id' => $lecturer->id,
        'room_id' => $this->room->id,
        'day_of_week' => 1,
        'starts_at' => '18:00:00',
        'ends_at' => '19:00:00',
        'income' => 450,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.sessions.outcome', [
            'kind' => 'external',
            'id' => $schedule->id,
            'date' => '2026-08-17',
        ]))
        ->assertRedirect();

    $session = ClassSession::query()->first();

    expect($session->type)->toBe('external');
    expect($session->external_lecturer_schedule_id)->toBe($schedule->id);
    expect((float) $session->income)->toBe(0.0);
});

test('admin can record actual income after session ends', function () {
    $session = ClassSession::factory()->create([
        'type' => 'external',
        'room_id' => $this->room->id,
        'title' => 'محاضرة',
        'income' => 0,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.sessions.update', $session), [
            'income' => 275,
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    $session->refresh();

    expect((float) $session->income)->toBe(275.0);
    expect($session->outcome_recorded_at)->not->toBeNull();
});

test('cannot record outcome before session starts', function () {
    $session = ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.sessions.update', $session), [
            'income' => 100,
        ])
        ->assertSessionHasErrors('income');

    expect($session->fresh()->outcome_recorded_at)->toBeNull();
});

test('schedule marks past teacher slots as pending outcome', function () {
    TeacherSchedule::query()->create([
        'teacher_id' => $this->teacher->id,
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '10:00:00',
        'ends_at' => '11:00:00',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-16']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->has('levelGroups')
            ->where('days.1.cells.0.items.0.session_status', 'pending')
            ->where('days.1.cells.0.items.0.occurrence_kind', 'teacher')
            ->where('days.1.cells.0.items.0.education_level_group_id', $this->level->education_level_group_id));
});

test('admin can cancel past session from schedule occurrence', function () {
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $this->teacher->id,
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '10:00:00',
        'ends_at' => '11:00:00',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.occurrence.cancel', [
            'kind' => 'teacher',
            'id' => $schedule->id,
            'date' => '2026-08-16',
        ]))
        ->assertRedirect(route('admin.schedule.index', ['date' => '2026-08-16']));

    $session = ClassSession::query()->first();

    expect($session)->not->toBeNull();
    expect($session->canceled_at)->not->toBeNull();
    expect($session->outcome_recorded_at)->toBeNull();
});

test('admin can cancel upcoming session from schedule occurrence', function () {
    $future = now()->addWeek()->startOfDay()->setTime(10, 0);

    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $this->teacher->id,
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => (int) $future->dayOfWeek,
        'starts_at' => '10:00:00',
        'ends_at' => '11:00:00',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.occurrence.cancel', [
            'kind' => 'teacher',
            'id' => $schedule->id,
            'date' => $future->toDateString(),
        ]))
        ->assertRedirect(route('admin.schedule.index', ['date' => $future->toDateString()]));

    $session = ClassSession::query()->first();

    expect($session)->not->toBeNull();
    expect($session->canceled_at)->not->toBeNull();
    expect($session->starts_at->isFuture())->toBeTrue();
});

test('admin can cancel session from session page', function () {
    $session = ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.cancel', $session))
        ->assertRedirect(route('admin.sessions.index', ['filter' => 'pending']));

    $session->refresh();

    expect($session->canceled_at)->not->toBeNull();
    expect($session->outcome_recorded_at)->toBeNull();
});

test('admin can restore canceled session from session page', function () {
    $session = ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
        'canceled_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.restore', $session))
        ->assertRedirect(route('admin.sessions.show', $session));

    $session->refresh();

    expect($session->canceled_at)->toBeNull();
});

test('admin can restore canceled session from schedule occurrence', function () {
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $this->teacher->id,
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '10:00:00',
        'ends_at' => '11:00:00',
    ]);

    ClassSession::factory()->create([
        'teacher_schedule_id' => $schedule->id,
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'starts_at' => '2026-08-16 10:00:00',
        'ends_at' => '2026-08-16 11:00:00',
        'canceled_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.occurrence.restore', [
            'kind' => 'teacher',
            'id' => $schedule->id,
            'date' => '2026-08-16',
        ]))
        ->assertRedirect(route('admin.schedule.index', ['date' => '2026-08-16']));

    $session = ClassSession::query()->first();

    expect($session->canceled_at)->toBeNull();
});

test('restored past session appears in pending list', function () {
    $session = ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHour(),
        'canceled_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.restore', $session));

    $this->actingAs($this->admin)
        ->get(route('admin.sessions.index', ['filter' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/sessions/index')
            ->where('sessions.total', 1));
});

test('cannot restore session that is not canceled', function () {
    $session = ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.restore', $session))
        ->assertSessionHasErrors('session');

    expect($session->fresh()->canceled_at)->toBeNull();
});

test('cannot cancel session with recorded outcome', function () {
    $session = ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
        'outcome_recorded_at' => now(),
        'income' => 200,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.cancel', $session))
        ->assertSessionHasErrors('session');

    expect($session->fresh()->canceled_at)->toBeNull();
});

test('canceled sessions are excluded from pending list', function () {
    ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHour(),
        'canceled_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.sessions.index', ['filter' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/sessions/index')
            ->where('sessions.total', 0));
});

test('schedule marks canceled teacher slots', function () {
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $this->teacher->id,
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '10:00:00',
        'ends_at' => '11:00:00',
    ]);

    ClassSession::factory()->create([
        'teacher_schedule_id' => $schedule->id,
        'teacher_id' => $this->teacher->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'starts_at' => '2026-08-16 10:00:00',
        'ends_at' => '2026-08-16 11:00:00',
        'canceled_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-16']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->where('days.1.cells.0.items.0.session_status', 'canceled'));
});

test('schedule does not flag level conflicts for different levels in the same group', function () {
    $group = EducationLevelGroup::factory()->create();
    $firstLevel = EducationLevel::factory()->create(['education_level_group_id' => $group->id]);
    $secondLevel = EducationLevel::factory()->create(['education_level_group_id' => $group->id]);
    $otherSubject = Subject::factory()->create();
    $otherRoom = Room::factory()->create();

    Teacher::factory()->create()->schedules()->create([
        'education_level_id' => $firstLevel->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '16:00:00',
        'ends_at' => '17:30:00',
    ]);

    Teacher::factory()->create()->schedules()->create([
        'education_level_id' => $secondLevel->id,
        'subject_id' => $otherSubject->id,
        'room_id' => $otherRoom->id,
        'day_of_week' => 0,
        'starts_at' => '17:00:00',
        'ends_at' => '18:00:00',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-16']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->where('days.1.cells.0.items.0.has_level_conflict', false)
            ->where('days.1.cells.0.items.0.has_room_conflict', false)
            ->where('days.1.cells.1.items.0.has_level_conflict', false)
            ->where('days.1.cells.1.items.0.has_room_conflict', false));
});

test('schedule flags conflicts for overlapping sessions in the same education level', function () {
    $otherSubject = Subject::factory()->create();

    Teacher::factory()->create()->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '16:00:00',
        'ends_at' => '17:30:00',
    ]);

    Teacher::factory()->create()->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $otherSubject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '17:00:00',
        'ends_at' => '18:00:00',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-16']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->has('days.1.cells.0.items', 2)
            ->where('days.1.cells.0.items.0.has_level_conflict', true)
            ->where('days.1.cells.0.items.0.has_room_conflict', true)
            ->where('days.1.cells.0.items.1.has_level_conflict', true)
            ->where('days.1.cells.0.items.1.has_room_conflict', true)
            ->where('days.1.cells.0.items.0.education_level_id', $this->level->id)
            ->where('days.1.cells.0.items.1.education_level_id', $this->level->id));
});

test('reports exclude sessions without recorded outcomes', function () {
    ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'income' => 500,
        'starts_at' => now()->subDay()->setTime(10, 0),
        'ends_at' => now()->subDay()->setTime(11, 0),
        'outcome_recorded_at' => null,
    ]);

    ClassSession::factory()->create([
        'room_id' => $this->room->id,
        'income' => 200,
        'starts_at' => now()->subDay()->setTime(14, 0),
        'ends_at' => now()->subDay()->setTime(15, 0),
        'outcome_recorded_at' => now(),
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('admin.reports.index', [
            'from' => now()->subDays(2)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('totals.income', 200));
});
