<?php

use App\Models\Expense;
use App\Models\User;

beforeEach(function () {
    $this->superadmin = User::factory()->superadmin()->create();
});

test('superadmin can create an expense', function () {
    $this->actingAs($this->superadmin)
        ->post(route('admin.expenses.store'), [
            'date' => now()->toDateString(),
            'amount' => 120.5,
            'description' => 'فاتورة كهرباء',
        ])
        ->assertRedirect(route('admin.expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'amount' => 120.5,
        'description' => 'فاتورة كهرباء',
    ]);
});

test('expense requires amount and description', function () {
    $this->actingAs($this->superadmin)
        ->post(route('admin.expenses.store'), [
            'date' => now()->toDateString(),
        ])
        ->assertSessionHasErrors(['amount', 'description']);
});

test('superadmin can delete an expense', function () {
    $expense = Expense::factory()->create();

    $this->actingAs($this->superadmin)
        ->delete(route('admin.expenses.destroy', $expense))
        ->assertRedirect(route('admin.expenses.index'));

    $this->assertSoftDeleted($expense);
});

test('regular admin can access and create expenses', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.expenses.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.expenses.store'), [
            'date' => now()->toDateString(),
            'amount' => 75,
            'description' => 'مصروف نثري',
        ])
        ->assertRedirect(route('admin.expenses.index'));

    $this->assertDatabaseHas('expenses', ['description' => 'مصروف نثري']);
});
