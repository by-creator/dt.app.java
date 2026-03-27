<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'create:admin-user';

    protected $description = 'Create the default admin role and user when none exists.';

    public function handle(): int
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            $this->components->warn('Roles or users table does not exist yet.');

            return self::SUCCESS;
        }

        $userRole = Role::firstOrCreate(['name' => 'USER']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);

        User::query()
            ->whereNull('role_id')
            ->update(['role_id' => $userRole->id]);

        $admin = User::query()
            ->where('role_id', $adminRole->id)
            ->first();

        if ($admin) {
            $this->components->info('An admin user already exists.');

            return self::SUCCESS;
        }

        $existingAdminEmail = User::query()
            ->where('email', 'admin@dakar-terminal.com')
            ->first();

        if ($existingAdminEmail) {
            $existingAdminEmail->forceFill([
                'name' => 'Admin',
                'role_id' => $adminRole->id,
            ])->save();

            $this->components->info('Existing user promoted to admin.');

            return self::SUCCESS;
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@dakar-terminal.com',
            'password' => 'passer1234',
            'email_verified_at' => now(),
            'role_id' => $adminRole->id,
        ]);

        $this->components->info('Default admin user created successfully.');

        return self::SUCCESS;
    }
}
