<?php

namespace App\Console\Commands;

use App\Enums\PermissionName;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Entrust\EntrustCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpgradeCommand extends Command
{
    protected $signature = 'daybyday:upgrade';

    protected $description = 'Safely upgrade DaybydayCRM - fix missing permissions and role assignments. Safe to run on production with existing users.';

    public function handle()
    {
        $this->info('🚀 Starting DaybydayCRM upgrade...');
        $this->newLine();

        $createdCount = $this->ensureAllPermissionsExist();
        $this->newLine();

        $syncedCount = $this->ensureRolesHaveAllPermissions();
        $this->newLine();

        $this->ensureFirstUserHasOwnerRole();
        $this->newLine();

        $this->flushEntrustCache();

        $this->info('Upgrade complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Permissions Created', $createdCount],
                ['Permissions Synced to Roles', $syncedCount],
            ]
        );

        return 0;
    }

    /**
     * Ensure all permissions from PermissionName enum exist in database
     */
    private function ensureAllPermissionsExist(): int
    {
        $this->info('Checking permissions...');

        $requiredPermissions = $this->getRequiredPermissions();
        $createdCount = 0;

        foreach ($requiredPermissions as $name => $data) {
            $exists = Permission::where('name', $name)->exists();

            if (!$exists) {
                Permission::query()->create([
                    'external_id'  => Str::uuid()->toString(),
                    'display_name' => $data['display_name'],
                    'name'         => $name,
                    'description'  => $data['description'],
                    'grouping'     => $data['grouping'],
                ]);
                $this->line("   ✓ Created: {$name}");
                $createdCount++;
            }
        }

        if ($createdCount === 0) {
            $this->info('   All permissions already exist');
        }

        return $createdCount;
    }

    /**
     * Ensure owner, admin, and administrator roles have all permissions
     */
    private function ensureRolesHaveAllPermissions(): int
    {
        $this->info('Syncing permissions to roles...');

        $allPermissions = Permission::all()->pluck('id')->toArray();
        $syncedCount = 0;

        // Include 'admin' which is used in tests alongside 'administrator'
        $roles = Role::query()->whereIn('name', ['owner', 'administrator', 'admin'])->get();

        if ($roles->isEmpty()) {
            $this->warn('   ⚠ No owner, administrator, or admin roles found!');
            return 0;
        }

        foreach ($roles as $role) {
            $currentPerms = $role->perms()->pluck('id')->toArray();
            $missingPerms = array_diff($allPermissions, $currentPerms);

            if (!empty($missingPerms)) {
                $role->perms()->attach($missingPerms);
                $syncedCount += count($missingPerms);
                $this->line("Added " . count($missingPerms) . " permissions to {$role->display_name} role");
            } else {
                $this->line("{$role->display_name} role already has all permissions");
            }
        }

        return $syncedCount;
    }

    /**
     * Get all required permissions with their metadata.
     * Uses PermissionName enum as the single source of truth.
     */
    private function getRequiredPermissions(): array
    {
        return PermissionName::allPermissions();
    }

    /**
     * Ensure the first user (the admin seed user) is assigned to the owner role.
     * Safe to run on existing data — uses syncWithoutDetaching.
     */
    private function ensureFirstUserHasOwnerRole(): void
    {
        $this->info('Checking user → role assignment...');

        $ownerRole = Role::where('name', 'owner')->first();
        if (! $ownerRole) {
            $this->warn('   ⚠ Owner role not found — skipping user role assignment');
            return;
        }

        $firstUser = User::orderBy('id')->first();
        if (! $firstUser) {
            $this->warn('   ⚠ No users found — skipping user role assignment');
            return;
        }

        $hasOwnerRole = $firstUser->roles()->where('name', 'owner')->exists();
        if (! $hasOwnerRole) {
            $firstUser->roles()->syncWithoutDetaching([$ownerRole->id]);
            $this->line("   ✓ Assigned '{$firstUser->email}' to the owner role");
        } else {
            $this->line("   ✓ '{$firstUser->email}' already has the owner role");
        }
    }

    /**
     * Flush Entrust and Laravel caches for roles and permissions
     */
    private function flushEntrustCache(): void
    {
        $this->info('Flushing Entrust cache...');
        EntrustCacheService::clear();
        $this->line('Cache flushed successfully');
    }
}

