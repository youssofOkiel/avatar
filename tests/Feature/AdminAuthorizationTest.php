<?php

use App\Models\EducationLevel;
use App\Models\User;

test('guest is redirected from admin area', function () {
    $this->get(route('admin.reservations.index'))
        ->assertRedirect(route('login'));
});

test('admin can create a subject linked to multiple levels', function () {
    $admin = User::factory()->admin()->create();
    $first = EducationLevel::factory()->create();
    $second = EducationLevel::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.subjects.store'), [
            'name' => 'الرياضيات',
            'education_level_ids' => [$first->id, $second->id],
        ])
        ->assertRedirect(route('admin.subjects.index'));

    $this->assertDatabaseHas('subjects', ['name' => 'الرياضيات']);
    $this->assertDatabaseCount('subject_education_level', 2);
});
