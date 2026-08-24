<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('regular admins are redirected from the dashboard to reservations', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertRedirect(route('admin.reservations.index'));
});

test('superadmin sees the dashboard with stats and finance', function () {
    $user = User::factory()->superadmin()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('stats')
            ->has('finance'));
});
