<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Assigns the owner role to the first (admin) user.
 * Resolves role by name – never by hard-coded ID.
 */
class UserRoleTableSeeder extends Seeder
{
    public function run(): void
    {
        $ownerRole = Role::query()->where('name', Role::OWNER_ROLE)->first();
        $adminUser = User::query()->orderBy('id')->first();

        if (! $ownerRole || ! $adminUser) {
            $this->command->warn('UserRoleTableSeeder: owner role or first user not found, skipping.');
            return;
        }

        $adminUser->roles()->syncWithoutDetaching([$ownerRole->id]);
    }
}
