<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Assigns all permissions to owner and administrator roles.
 * Uses syncWithoutDetaching so re-seeding never creates duplicates.
 * Role names are resolved by name constant, never by hard-coded ID.
 */
class RolePermissionTableSeeder extends Seeder
{
    /** Roles that receive every permission. */
    private array $fullAccessRoles = [
        Role::OWNER_ROLE,
        Role::ADMIN_ROLE,
    ];

    public function run(): void
    {
        $allPermissionIds = Permission::all()->pluck('id')->toArray();

        foreach ($this->fullAccessRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                $this->command->warn("RolePermissionTableSeeder: role '{$roleName}' not found, skipping.");
                continue;
            }

            $role->perms()->syncWithoutDetaching($allPermissionIds);
        }
    }
}
