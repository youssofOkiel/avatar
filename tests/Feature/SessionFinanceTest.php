<?php

use App\Models\ClassSession;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->room = Room::factory()->create();
});

test('admin can create a rental session without teacher or subject', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.sessions.store'), [
            'type' => 'rental',
            'title' => 'حجز قاعة لمؤتمر',
            'room_id' => $this->room->id,
            'income' => 500,
            'attendance_count' => 30,
            'starts_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(13, 0)->format('Y-m-d\TH:i'),
        ]);

    $session = ClassSession::query()->first();

    expect($session)->not->toBeNull();
    expect($session->type)->toBe('rental');
    expect($session->teacher_id)->toBeNull();
    expect($session->subject_id)->toBeNull();
    expect($session->title)->toBe('حجز قاعة لمؤتمر');
    expect((int) $session->attendance_count)->toBe(30);
    expect((float) $session->income)->toBe(0.0);
});

test('rental session requires a title', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.sessions.store'), [
            'type' => 'rental',
            'room_id' => $this->room->id,
            'starts_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(13, 0)->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors('title');
});

test('subject session requires teacher and subject', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.sessions.store'), [
            'type' => 'subject',
            'room_id' => $this->room->id,
            'starts_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->setTime(13, 0)->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors(['teacher_id', 'subject_id']);
});

test('admin can record optional attendance count for a subject session', function () {
    $session = ClassSession::factory()->create([
        'type' => 'subject',
        'teacher_id' => Teacher::factory(),
        'subject_id' => Subject::factory(),
        'room_id' => $this->room->id,
        'income' => 0,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.sessions.update', $session), [
            'income' => 250,
            'attendance_count' => 18,
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    $session->refresh();
    expect((float) $session->income)->toBe(250.0);
    expect((int) $session->attendance_count)->toBe(18);
    expect($session->outcome_recorded_at)->not->toBeNull();
});

test('attendance count is optional when recording outcome', function () {
    $session = ClassSession::factory()->create([
        'type' => 'subject',
        'teacher_id' => Teacher::factory(),
        'subject_id' => Subject::factory(),
        'room_id' => $this->room->id,
        'income' => 0,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.sessions.update', $session), [
            'income' => 100,
            'attendance_count' => '',
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    $session->refresh();
    expect($session->attendance_count)->toBeNull();
    expect((float) $session->income)->toBe(100.0);
    expect($session->outcome_recorded_at)->not->toBeNull();
});

test('admin can record headcount for a rental session', function () {
    $session = ClassSession::factory()->rental()->create([
        'room_id' => $this->room->id,
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subHour(),
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.sessions.update', $session), [
            'income' => 700,
            'attendance_count' => 42,
        ])
        ->assertRedirect(route('admin.sessions.show', $session));

    $session->refresh();
    expect((int) $session->attendance_count)->toBe(42);
    expect((float) $session->income)->toBe(700.0);
    expect($session->outcome_recorded_at)->not->toBeNull();
});
