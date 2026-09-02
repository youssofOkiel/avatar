<?php

use App\Models\ClassSession;
use App\Models\EducationLevel;
use App\Models\EducationLevelGroup;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSchedule;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('teacher phone must be unique', function () {
    Teacher::factory()->create(['phone' => '01011111111']);

    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $subject->educationLevels()->attach($level->id);

    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم مكرر',
            'phone' => '01011111111',
            'selections' => [
                ['education_level_id' => $level->id, 'subject_id' => $subject->id],
            ],
        ])
        ->assertSessionHasErrors('phone');
});

test('teacher can keep their own phone on update', function () {
    $teacher = Teacher::factory()->create(['phone' => '01022222222']);
    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $subject->educationLevels()->attach($level->id);
    $teacher->teacherSubjects()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'phone' => '01022222222',
            'selections' => [
                ['education_level_id' => $level->id, 'subject_id' => $subject->id],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();
});

test('teachers index can be filtered by education level group', function () {
    $groupA = EducationLevelGroup::factory()->create();
    $groupB = EducationLevelGroup::factory()->create();
    $levelA = EducationLevel::factory()->create(['education_level_group_id' => $groupA->id]);
    $levelB = EducationLevel::factory()->create(['education_level_group_id' => $groupB->id]);
    $subject = Subject::factory()->create();

    $teacherA = Teacher::factory()->create();
    $teacherB = Teacher::factory()->create();
    $teacherA->teacherSubjects()->create([
        'education_level_id' => $levelA->id,
        'subject_id' => $subject->id,
    ]);
    $teacherB->teacherSubjects()->create([
        'education_level_id' => $levelB->id,
        'subject_id' => $subject->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.teachers.index', ['group' => $groupA->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/teachers/index')
            ->has('teachers.data', 1)
            ->where('teachers.data.0.id', $teacherA->id)
            ->where('filters.group', $groupA->id));
});

test('session occurrence end time can be shorter or longer than planned', function () {
    $room = Room::factory()->create();
    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $teacher->id,
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'room_id' => $room->id,
        'day_of_week' => 2,
        'starts_at' => '09:00',
        'ends_at' => '13:00',
    ]);

    $session = ClassSession::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'education_level_id' => $level->id,
        'room_id' => $room->id,
        'teacher_schedule_id' => $schedule->id,
        'starts_at' => '2026-08-25 09:00:00',
        'ends_at' => '2026-08-25 13:00:00',
        'outcome_recorded_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.sessions.update', $session), [
            'income' => 100,
            'ends_at' => '12:30',
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    expect($session->fresh()->ends_at->format('H:i'))->toBe('12:30');
    expect(substr((string) $schedule->fresh()->ends_at, 0, 5))->toBe('13:00');

    $this->actingAs($this->admin)
        ->patch(route('admin.sessions.update', $session), [
            'income' => 100,
            'ends_at' => '13:45',
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    expect($session->fresh()->ends_at->format('H:i'))->toBe('13:45');
});

test('schedule reflects occurrence end time for room conflicts', function () {
    $room = Room::factory()->create();
    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $teacher = Teacher::factory()->create();
    $schedule = TeacherSchedule::query()->create([
        'teacher_id' => $teacher->id,
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'room_id' => $room->id,
        'day_of_week' => 2,
        'starts_at' => '09:00',
        'ends_at' => '13:00',
    ]);

    ClassSession::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'education_level_id' => $level->id,
        'room_id' => $room->id,
        'teacher_schedule_id' => $schedule->id,
        'starts_at' => '2026-08-25 09:00:00',
        'ends_at' => '2026-08-25 13:45:00',
    ]);

    Teacher::factory()->create()->schedules()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'room_id' => $room->id,
        'day_of_week' => 2,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-25']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->where('days.3.cells.0.items.0.ends_at', '13:45')
            ->where('days.3.cells.0.items.0.planned_ends_at', '13:00')
            ->where('days.3.cells.0.items.0.has_room_conflict', true)
            ->where('days.3.cells.0.items.1.has_room_conflict', true));
});
