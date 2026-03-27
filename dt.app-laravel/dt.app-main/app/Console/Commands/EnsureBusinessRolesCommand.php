<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class EnsureBusinessRolesCommand extends Command
{
    protected $signature = 'roles:ensure-business';

    protected $description = 'Create the required business roles when they do not exist yet.';

    private const ROLES = [
        'DIRECTION_GENERALE',
        'DIRECTION_FINANCIERE',
        'DIRECTION_EXPLOITATION',
        'INFORMATIQUE',
        'FACTURATION',
        'CONTROLE_DE_GESTION',
        'COMPTABILITE',
        'SERVICE_GENERAUX',
        'PLANIFICATION',
        'RESSOURCES_HUMAINES',
        'JURIDIQUE',
        'OPERATIONS',
        'QHSE',
        'DOUANE',
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('roles')) {
            $this->components->warn('Roles table does not exist yet.');

            return self::SUCCESS;
        }

        $created = 0;

        foreach (self::ROLES as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($role->wasRecentlyCreated) {
                $created++;
                $this->components->info("Role {$roleName} created.");
            }
        }

        if ($created === 0) {
            $this->components->info('All business roles already exist.');
        } else {
            $this->components->info("{$created} business role(s) created successfully.");
        }

        return self::SUCCESS;
    }
}
