<?php

use App\Models\User;

test('both admin and superadmin can reach operational tabs', function () {
    foreach ([User::factory()->admin()->create(), User::factory()->superadmin()->create()] as $user) {
        $this->actingAs($user)
            ->get(route('admin.reservations.index'))
            ->assertOk();
        $this->actingAs($user)
            ->get(route('admin.teachers.index'))
            ->assertOk();
        $this->actingAs($user)
            ->get(route('admin.external-lecturers.index'))
            ->assertOk();
        $this->actingAs($user)
            ->get(route('admin.expenses.index'))
            ->assertOk();
    }
});

test('superadmin can reach dashboard, expenses and reports', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)->get(route('dashboard'))->assertOk();
    $this->actingAs($superadmin)->get(route('admin.expenses.index'))->assertOk();
    $this->actingAs($superadmin)->get(route('admin.reports.index'))->assertOk();
});

test('regular admin is blocked from superadmin reports page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.reports.index'))->assertForbidden();
});
