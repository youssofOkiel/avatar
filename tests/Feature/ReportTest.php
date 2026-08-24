<?php

use App\Models\ClassSession;
use App\Models\Expense;
use App\Models\Room;
use App\Models\User;

beforeEach(function () {
    $this->superadmin = User::factory()->superadmin()->create();
    $this->room = Room::factory()->create();
});

test('report aggregates income, attendance and expenses per day', function () {
    $day = now()->startOfDay()->setTime(12, 0);

    $subjectSession = ClassSession::factory()->create([
        'type' => 'subject',
        'room_id' => $this->room->id,
        'income' => 200,
        'attendance_count' => 1,
        'starts_at' => $day,
        'ends_at' => (clone $day)->addHour(),
        'outcome_recorded_at' => now(),
    ]);

    ClassSession::factory()->rental()->create([
        'room_id' => $this->room->id,
        'income' => 300,
        'attendance_count' => 10,
        'starts_at' => (clone $day)->addHours(2),
        'ends_at' => (clone $day)->addHours(4),
        'outcome_recorded_at' => now(),
    ]);

    Expense::factory()->create([
        'date' => $day->toDateString(),
        'amount' => 150,
    ]);

    $this->actingAs($this->superadmin)
        ->get(route('admin.reports.index', [
            'from' => $day->toDateString(),
            'to' => $day->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reports/index')
            ->where('totals.sessions', 2)
            ->where('totals.attendance', 11)
            ->where('totals.income', 500)
            ->where('totals.expenses', 150)
            ->where('totals.net', 350));
});

test('regular admin cannot access reports', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});
