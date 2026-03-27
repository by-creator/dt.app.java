<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates the default admin user when none exists', function () {
    $this->artisan('create:admin-user')
        ->expectsOutputToContain('Default admin user created successfully.')
        ->assertSuccessful();

    $adminRole = Role::where('name', 'ADMIN')->first();

    expect($adminRole)->not->toBeNull();

    $admin = User::where('email', 'admin@dakar-terminal.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role_id)->toBe($adminRole->id)
        ->and($admin->role->name)->toBe('ADMIN')
        ->and(password_verify('passer1234', $admin->password))->toBeTrue();
});

it('does not create a second admin user when one already exists', function () {
    $adminRole = Role::firstWhere('name', 'ADMIN');

    User::factory()->create([
        'email' => 'existing-admin@dakar-terminal.com',
        'role_id' => $adminRole->id,
    ]);

    $this->artisan('create:admin-user')
        ->expectsOutputToContain('An admin user already exists.')
        ->assertSuccessful();

    expect(User::where('role_id', $adminRole->id)->count())->toBe(1);
});
