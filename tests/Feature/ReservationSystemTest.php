<?php

use App\Models\ClassSession;
use App\Models\EducationLevel;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin can create a teacher with per-level subjects and schedule', function () {
    $first = EducationLevel::factory()->create();
    $second = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $subject->educationLevels()->attach([$first->id, $second->id]);
    $room = Room::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'أحمد محمود',
            'is_active' => true,
            'selections' => [
                ['education_level_id' => $first->id, 'subject_id' => $subject->id],
            ],
            'schedules' => [
                [
                    'education_level_id' => $first->id,
                    'subject_id' => $subject->id,
                    'room_id' => $room->id,
                    'day_of_week' => 0,
                    'starts_at' => '16:00',
                    'ends_at' => '17:30',
                ],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'));

    $teacher = Teacher::query()->with('teacherSubjects', 'schedules')->first();

    expect($teacher->teacherSubjects)->toHaveCount(1);
    expect($teacher->teacherSubjects->first()->education_level_id)->toBe($first->id);
    expect($teacher->schedules)->toHaveCount(1);
    expect($teacher->schedules->first()->education_level_id)->toBe($first->id);
});

test('same subject can be selected for one level but not another', function () {
    $first = EducationLevel::factory()->create();
    $second = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $subject->educationLevels()->attach([$first->id, $second->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'أحمد',
            'selections' => [
                ['education_level_id' => $first->id, 'subject_id' => $subject->id],
            ],
        ])
        ->assertRedirect(route('admin.teachers.index'));

    $this->assertDatabaseHas('teacher_subject', [
        'education_level_id' => $first->id,
        'subject_id' => $subject->id,
    ]);
    $this->assertDatabaseMissing('teacher_subject', [
        'education_level_id' => $second->id,
        'subject_id' => $subject->id,
    ]);
});

test('admin can add an exceptional class session and is redirected to it', function () {
    $teacher = Teacher::factory()->create();
    $subject = Subject::factory()->create();
    $room = Room::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.sessions.store'), [
            'type' => 'subject',
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'income' => 150,
            'starts_at' => now()->addDay()->setTime(16, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i'),
        ]);

    $session = ClassSession::query()->first();
    expect($session)->not->toBeNull();

    $this->actingAs($this->admin)
        ->get(route('admin.sessions.show', $session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/sessions/show')
            ->has('availableStudents'));
});

test('admin can delete a class session', function () {
    $session = ClassSession::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.sessions.destroy', $session))
        ->assertRedirect(route('admin.sessions.index'));

    expect(ClassSession::query()->count())->toBe(0);
});
