<?php

use App\Models\ClassSession;
use App\Models\EducationLevel;
use App\Models\ExternalLecturer;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->room = Room::factory()->create(['name' => 'قاعة 1']);
    $this->level = EducationLevel::factory()->create();
    $this->subject = Subject::factory()->create();
});

test('schedule shows recurring schedules and dated sessions in day-by-room cells', function () {
    // Week of 2026-08-15 (Saturday) .. 2026-08-21 (Friday).
    $teacher = Teacher::factory()->create();
    $teacher->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0, // Sunday
        'starts_at' => '16:00',
        'ends_at' => '17:30',
    ]);

    $lecturer = ExternalLecturer::factory()->create();
    $lecturer->schedules()->create([
        'topic' => 'محاضرة خارجية',
        'room_id' => $this->room->id,
        'day_of_week' => 1, // Monday
        'starts_at' => '18:00',
        'ends_at' => '19:00',
        'income' => 300,
    ]);

    ClassSession::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'type' => 'subject',
        'starts_at' => '2026-08-18 14:00:00', // Tuesday
        'ends_at' => '2026-08-18 15:00:00',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-18']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->where('week.start', '2026-08-15')
            ->where('week.end', '2026-08-21')
            ->has('rooms', 1)
            ->has('days', 7)
            ->where('days.0.day_of_week', 6) // Saturday
            ->where('days.0.cells.0.room_id', $this->room->id)
            ->has('days.0.cells.0.items', 0)
            ->has('days.1.cells.0.items', 1) // Sunday teacher
            ->where('days.1.cells.0.items.0.kind', 'teacher')
            ->where('days.1.cells.0.items.0.has_level_conflict', false)
            ->where('days.1.cells.0.items.0.has_room_conflict', false)
            ->has('days.2.cells.0.items', 1) // Monday external
            ->where('days.2.cells.0.items.0.kind', 'external')
            ->has('days.3.cells.0.items', 1) // Tuesday session
            ->where('days.3.cells.0.items.0.kind', 'session')
            ->has('days.4.cells.0.items', 0));
});

test('schedule defaults to the current week and is reachable by admins', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->has('days', 7)
            ->has('rooms')
            ->has('week.prev')
            ->has('week.next'));
});

test('overlapping same-level teacher schedules are marked as conflicts', function () {
    $otherSubject = Subject::factory()->create();
    $first = Teacher::factory()->create();
    $second = Teacher::factory()->create();

    $first->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '16:00',
        'ends_at' => '17:30',
    ]);

    $second->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $otherSubject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '17:00',
        'ends_at' => '18:00',
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
            ->where('days.1.cells.0.items.0.education_level_id', $this->level->id));
});

test('non overlapping same-level schedules are not marked as conflicts', function () {
    $otherSubject = Subject::factory()->create();
    $first = Teacher::factory()->create();
    $second = Teacher::factory()->create();

    $first->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '14:00',
        'ends_at' => '15:00',
    ]);

    $second->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $otherSubject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '15:00',
        'ends_at' => '16:00',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-16']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->has('days.1.cells.0.items', 2)
            ->where('days.1.cells.0.items.0.has_level_conflict', false)
            ->where('days.1.cells.0.items.0.has_room_conflict', false)
            ->where('days.1.cells.0.items.1.has_level_conflict', false)
            ->where('days.1.cells.0.items.1.has_room_conflict', false));
});

test('overlapping teacher and external lecturer in the same room are marked as conflicts', function () {
    Teacher::factory()->create()->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 6,
        'starts_at' => '10:00',
        'ends_at' => '11:00',
    ]);

    ExternalLecturer::factory()->create()->schedules()->create([
        'topic' => 'dddd',
        'room_id' => $this->room->id,
        'day_of_week' => 6,
        'starts_at' => '10:00',
        'ends_at' => '11:00',
        'income' => 0,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-22']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/schedule/index')
            ->has('days.0.cells.0.items', 2)
            ->where('days.0.cells.0.items.0.has_room_conflict', true)
            ->where('days.0.cells.0.items.0.has_level_conflict', false)
            ->where('days.0.cells.0.items.1.has_room_conflict', true)
            ->where('days.0.cells.0.items.1.has_level_conflict', false));
});
