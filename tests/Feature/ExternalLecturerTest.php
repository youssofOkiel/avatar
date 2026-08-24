<?php

use App\Models\EducationLevel;
use App\Models\ExternalLecturer;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->room = Room::factory()->create();
});

test('admin can create an external lecturer with schedule slots', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.external-lecturers.store'), [
            'name' => 'محاضر خارجي',
            'schedules' => [
                [
                    'topic' => 'ورشة برمجة',
                    'room_id' => $this->room->id,
                    'day_of_week' => 1,
                    'starts_at' => '16:00',
                    'ends_at' => '18:00',
                    'income' => 500,
                ],
            ],
        ])
        ->assertRedirect(route('admin.external-lecturers.index'))
        ->assertSessionHasNoErrors();

    $lecturer = ExternalLecturer::query()->firstOrFail();

    expect($lecturer->name)->toBe('محاضر خارجي');
    expect($lecturer->schedules)->toHaveCount(1);

    $slot = $lecturer->schedules->first();
    expect($slot->topic)->toBe('ورشة برمجة');
    expect($slot->room_id)->toBe($this->room->id);
    expect($slot->day_of_week)->toBe(1);
    expect((float) $slot->income)->toBe(500.0);
});

test('end time must be after start time', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.external-lecturers.store'), [
            'name' => 'محاضر خارجي',
            'schedules' => [
                [
                    'topic' => 'محاضرة',
                    'room_id' => $this->room->id,
                    'day_of_week' => 1,
                    'starts_at' => '18:00',
                    'ends_at' => '16:00',
                    'income' => 0,
                ],
            ],
        ])
        ->assertSessionHasErrors('schedules.0.ends_at');
});

test('room is optional on a schedule slot', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.external-lecturers.store'), [
            'name' => 'محاضر بدون قاعة',
            'schedules' => [
                [
                    'topic' => 'محاضرة',
                    'day_of_week' => 2,
                    'starts_at' => '10:00',
                    'ends_at' => '11:00',
                ],
            ],
        ])
        ->assertRedirect(route('admin.external-lecturers.index'))
        ->assertSessionHasNoErrors();

    expect(ExternalLecturer::query()->firstOrFail()->schedules->first()->room_id)->toBeNull();
});

test('admin can update an external lecturer and rebuild schedules', function () {
    $lecturer = ExternalLecturer::factory()->create(['name' => 'قديم']);
    $lecturer->schedules()->create([
        'topic' => 'قديم',
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '16:00',
        'ends_at' => '17:00',
        'income' => 100,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.external-lecturers.update', $lecturer), [
            'name' => 'جديد',
            'schedules' => [
                [
                    'topic' => 'جديد',
                    'room_id' => $this->room->id,
                    'day_of_week' => 3,
                    'starts_at' => '14:00',
                    'ends_at' => '15:00',
                    'income' => 250,
                ],
            ],
        ])
        ->assertRedirect(route('admin.external-lecturers.index'))
        ->assertSessionHasNoErrors();

    $lecturer->refresh()->load('schedules');

    expect($lecturer->name)->toBe('جديد');
    expect($lecturer->schedules)->toHaveCount(1);
    expect($lecturer->schedules->first()->topic)->toBe('جديد');
    expect($lecturer->schedules->first()->day_of_week)->toBe(3);
});

test('admin can delete an external lecturer', function () {
    $lecturer = ExternalLecturer::factory()->create();
    $lecturer->schedules()->create([
        'topic' => 'محاضرة',
        'room_id' => $this->room->id,
        'day_of_week' => 0,
        'starts_at' => '16:00',
        'ends_at' => '17:00',
        'income' => 0,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.external-lecturers.destroy', $lecturer))
        ->assertRedirect(route('admin.external-lecturers.index'));

    expect(ExternalLecturer::query()->count())->toBe(0);
    expect(ExternalLecturer::withTrashed()->find($lecturer->id))->not->toBeNull();
    $this->assertSoftDeleted($lecturer);
    $this->assertSoftDeleted('external_lecturer_schedules', [
        'external_lecturer_id' => $lecturer->id,
    ]);
});

test('external lecturer cannot take a room already used by a teacher at the same time', function () {
    $level = EducationLevel::factory()->create();
    $subject = Subject::factory()->create();
    $subject->educationLevels()->attach($level->id);

    Teacher::factory()->create()->schedules()->create([
        'education_level_id' => $level->id,
        'subject_id' => $subject->id,
        'room_id' => $this->room->id,
        'day_of_week' => 6,
        'starts_at' => '10:00',
        'ends_at' => '11:00',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.external-lecturers.store'), [
            'name' => 'محاضر متعارض',
            'schedules' => [
                [
                    'topic' => 'محاضرة',
                    'room_id' => $this->room->id,
                    'day_of_week' => 6,
                    'starts_at' => '10:00',
                    'ends_at' => '11:00',
                    'income' => 0,
                ],
            ],
        ])
        ->assertSessionHasErrors('schedules.0.room_id');
});

test('editing an external lecturer does not conflict with its own schedule', function () {
    $lecturer = ExternalLecturer::factory()->create();
    $lecturer->schedules()->create([
        'topic' => 'محاضرة',
        'room_id' => $this->room->id,
        'day_of_week' => 2,
        'starts_at' => '12:00',
        'ends_at' => '13:00',
        'income' => 0,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.external-lecturers.update', $lecturer), [
            'name' => $lecturer->name,
            'schedules' => [
                [
                    'topic' => 'محاضرة',
                    'room_id' => $this->room->id,
                    'day_of_week' => 2,
                    'starts_at' => '12:00',
                    'ends_at' => '13:00',
                    'income' => 0,
                ],
            ],
        ])
        ->assertRedirect(route('admin.external-lecturers.index'))
        ->assertSessionHasNoErrors();
});
