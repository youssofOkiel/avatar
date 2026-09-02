<?php

use App\Models\EducationLevel;
use App\Models\ExternalLecturer;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->level = EducationLevel::factory()->create();
    $this->subject = Subject::factory()->create();
    $this->otherSubject = Subject::factory()->create();
    $this->subject->educationLevels()->attach($this->level->id);
    $this->otherSubject->educationLevels()->attach($this->level->id);
    $this->room = Room::factory()->create();

    $existing = Teacher::factory()->create();
    $existing->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '16:00',
        'ends_at' => '17:30',
    ]);
});

test('same level time overlap is allowed across teachers when rooms differ', function () {
    $otherRoom = Room::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم جديد',
            'phone' => '0191000001',
            'selections' => [
                ['education_level_id' => $this->level->id, 'subject_id' => $this->otherSubject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->otherSubject->id,
                    'room_id' => $otherRoom->id,
                    'day_of_week' => 0,
                    'starts_at' => '17:00',
                    'ends_at' => '18:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();
});

test('same level time overlap within submitted schedules is allowed when rooms differ', function () {
    $otherRoom = Room::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم جديد',
            'phone' => '0191000002',
            'selections' => [
                ['education_level_id' => $this->level->id, 'subject_id' => $this->subject->id],
                ['education_level_id' => $this->level->id, 'subject_id' => $this->otherSubject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => $this->room->id,
                    'day_of_week' => 3,
                    'starts_at' => '16:00',
                    'ends_at' => '17:30',
                ],
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->otherSubject->id,
                    'room_id' => $otherRoom->id,
                    'day_of_week' => 3,
                    'starts_at' => '17:00',
                    'ends_at' => '18:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();
});

test('same day is accepted when time ranges do not overlap', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم جديد',
            'phone' => '0191000003',
            'selections' => [
                ['education_level_id' => $this->level->id, 'subject_id' => $this->subject->id],
                ['education_level_id' => $this->level->id, 'subject_id' => $this->otherSubject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => $this->room->id,
                    'day_of_week' => 2,
                    'starts_at' => '14:00',
                    'ends_at' => '15:00',
                ],
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->otherSubject->id,
                    'room_id' => $this->room->id,
                    'day_of_week' => 2,
                    'starts_at' => '15:00',
                    'ends_at' => '16:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();
});

test('non overlapping day is accepted', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم جديد',
            'phone' => '0191000004',
            'selections' => [
                ['education_level_id' => $this->level->id, 'subject_id' => $this->subject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => $this->room->id,
                    'day_of_week' => 1,
                    'starts_at' => '16:00',
                    'ends_at' => '17:30',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();
});

test('editing a teacher does not conflict with its own schedule', function () {
    $teacher = Teacher::factory()->create();
    $teacher->teacherSubjects()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
    ]);
    $teacher->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 2,
        'starts_at' => '16:00',
        'ends_at' => '17:30',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'phone' => $teacher->phone,
            'selections' => [
                ['education_level_id' => $this->level->id, 'subject_id' => $this->subject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => $this->room->id,
                    'day_of_week' => 2,
                    'starts_at' => '16:00',
                    'ends_at' => '17:30',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();
});

test('admin can assign a room to an existing schedule that had none', function () {
    $teacher = Teacher::factory()->create();
    $teacher->teacherSubjects()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
    ]);
    $teacher->schedules()->create([
        'education_level_id' => $this->level->id,
        'subject_id' => $this->subject->id,
        'room_id' => null,
        'day_of_week' => 1,
        'starts_at' => '15:30',
        'ends_at' => '17:00',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.teachers.update', $teacher), [
            'name' => $teacher->name,
            'phone' => $teacher->phone,
            'selections' => [
                ['education_level_id' => $this->level->id, 'subject_id' => $this->subject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => $this->room->id,
                    'day_of_week' => 1,
                    'starts_at' => '15:30',
                    'ends_at' => '17:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();

    expect($teacher->fresh()->schedules()->value('room_id'))->toBe($this->room->id);
});

test('teacher schedule room is optional', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم بلا قاعة',
            'phone' => '0191000005',
            'selections' => [
                ['education_level_id' => $this->level->id, 'subject_id' => $this->subject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $this->level->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => null,
                    'day_of_week' => 4,
                    'starts_at' => '10:00',
                    'ends_at' => '11:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();

    expect(Teacher::query()->where('name', 'معلم بلا قاعة')->firstOrFail()->schedules->first()->room_id)->toBeNull();
});

test('teacher cannot take a room already used by an external lecturer at the same time', function () {
    $otherLevel = EducationLevel::factory()->create();
    $this->subject->educationLevels()->attach($otherLevel->id);

    ExternalLecturer::factory()->create()->schedules()->create([
        'topic' => 'محاضرة',
        'room_id' => $this->room->id,
        'day_of_week' => 5,
        'starts_at' => '10:00',
        'ends_at' => '11:00',
        'income' => 0,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم متعارض',
            'phone' => '0191000006',
            'selections' => [
                ['education_level_id' => $otherLevel->id, 'subject_id' => $this->subject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $otherLevel->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => $this->room->id,
                    'day_of_week' => 5,
                    'starts_at' => '10:00',
                    'ends_at' => '11:00',
                ],
            ],
        ])
        ->assertSessionHasErrors('schedules.0.room_id');
});

test('different rooms at the same time are allowed', function () {
    $otherRoom = Room::factory()->create();
    $otherLevel = EducationLevel::factory()->create();
    $this->subject->educationLevels()->attach($otherLevel->id);

    ExternalLecturer::factory()->create()->schedules()->create([
        'topic' => 'محاضرة',
        'room_id' => $this->room->id,
        'day_of_week' => 5,
        'starts_at' => '10:00',
        'ends_at' => '11:00',
        'income' => 0,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم قاعة أخرى',
            'phone' => '0191000007',
            'selections' => [
                ['education_level_id' => $otherLevel->id, 'subject_id' => $this->subject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $otherLevel->id,
                    'subject_id' => $this->subject->id,
                    'room_id' => $otherRoom->id,
                    'day_of_week' => 5,
                    'starts_at' => '10:00',
                    'ends_at' => '11:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'))
        ->assertSessionHasNoErrors();
});
