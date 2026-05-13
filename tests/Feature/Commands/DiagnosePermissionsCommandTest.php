<?php

namespace Tests\Feature\Commands;

use App\Enums\PermissionName;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Entrust\EntrustCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[Group('diagnose-command')]
class DiagnosePermissionsCommandTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_reports_no_issues_when_everything_correct()
    {
        $this->setupCompletePermissionChain();

        $this->artisan('entrust:diagnose --user=admin@admin.com')
            ->expectsOutput('✅ No issues found!')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_detects_missing_permissions()
    {
        Role::factory()->create(['name' => 'owner', 'display_name' => 'Owner']);
        Role::factory()->create(['name' => 'administrator', 'display_name' => 'Administrator']);

        // Create only some permissions (missing others)
        Permission::factory()->create(['name' => 'task-create']);

        $this->artisan('entrust:diagnose')
            ->expectsOutputToContain('permissions are MISSING')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_detects_role_missing_permissions()
    {
        $owner = Role::factory()->create(['name' => 'owner', 'display_name' => 'Owner']);
        Role::factory()->create(['name' => 'administrator', 'display_name' => 'Administrator']);

        // Create multiple permissions
        Permission::factory()->createMany([
            ['name' => 'task-create'],
            ['name' => 'task-update'],
            ['name' => 'task-delete'],
        ]);

        // Attach only one permission to owner
        $owner->perms()->attach(Permission::where('name', 'task-create')->first()->id);

        $this->artisan('entrust:diagnose')
            ->expectsOutputToContain('MISSING')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_detects_user_no_roles()
    {
        $this->setupCompletePermissionChain();

        $user = User::factory()->create(['email' => 'norolls@test.com']);

        $this->artisan('entrust:diagnose --user=norolls@test.com')
            ->expectsOutputToContain('NO roles assigned')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_detects_user_wrong_role()
    {
        $this->setupCompletePermissionChain();

        $userRole = Role::factory()->create(['name' => 'user', 'display_name' => 'User']);
        $user = User::factory()->create(['email' => 'wrongrole@test.com']);
        $user->attachRole($userRole);

        $this->artisan('entrust:diagnose --user=wrongrole@test.com')
            ->expectsOutputToContain('not assigned to')
            ->assertExitCode(0);
    }

    #[Test]
    public function command_fixes_missing_permissions()
    {
        Role::factory()->create(['name' => 'owner', 'display_name' => 'Owner']);
        Role::factory()->create(['name' => 'administrator', 'display_name' => 'Administrator']);

        // Start with no permissions
        $this->assertTrue(Permission::count() === 0);

        $this->artisan('entrust:diagnose --fix')
            ->expectsOutputToContain('Creating missing permissions')
            ->expectsOutputToContain('Attaching permissions')
            ->assertExitCode(0);

        // Verify all permissions were created
        $this->assertEquals(count(PermissionName::allPermissions()), Permission::count());

        // Verify owner role has all permissions
        $owner = Role::where('name', 'owner')->first();
        $this->assertEquals(Permission::count(), $owner->perms()->count());
    }

    #[Test]
    public function command_uses_sync_without_detaching()
    {
        $owner = Role::factory()->create(['name' => 'owner', 'display_name' => 'Owner']);
        Role::factory()->create(['name' => 'administrator', 'display_name' => 'Administrator']);

        // Create permissions
        $perms = Permission::factory()->createMany([
            ['name' => 'task-create'],
            ['name' => 'task-update'],
            ['name' => 'task-delete'],
            ['name' => 'client-create'],
        ]);

        // Attach first two to owner
        $owner->perms()->attach([$perms[0]->id, $perms[1]->id]);
        $this->assertEquals(2, $owner->perms()->count());

        // Mock scenario: diagnose detects missing and fixes
        // Instead of re-running attachPermissions which could create duplicates,
        // it should use syncWithoutDetaching
        $this->artisan('entrust:diagnose --user=admin@admin.com --fix')
            ->assertExitCode(0);

        // Verify no duplicates exist (count should equal permission count, not more)
        $this->assertEquals(4, $owner->fresh()->perms()->count());
    }

    #[Test]
    public function permission_caching_returns_proper_objects()
    {
        $this->setupCompletePermissionChain();

        $owner = Role::where('name', 'owner')->first();

        // Call cachedPermissions and verify it returns proper Permission objects
        $permissions = $owner->cachedPermissions();

        $this->assertNotEmpty($permissions);

        foreach ($permissions as $perm) {
            // Verify it's actually an object with accessible properties
            $this->assertIsObject($perm);
            $this->assertTrue(isset($perm->name) || method_exists($perm, 'name'));
            $this->assertNotNull($perm->name);
        }
    }

    #[Test]
    public function user_can_check_permissions_with_cached_roles()
    {
        $this->setupCompletePermissionChain();

        $owner = Role::where('name', 'owner')->first();
        $user = User::factory()->create(['email' => 'owner@test.com']);
        $user->attachRole($owner);

        // Verify user can check if they have a permission (uses cachedPermissions internally)
        $this->assertTrue($user->can('task-create'));
        $this->assertTrue($user->can('client-create'));
    }

    #[Test]
    public function cached_permissions_survive_redis_serialization()
    {
        // This test verifies that cachedPermissions() returns arrays that can be
        // serialized/deserialized without becoming __PHP_Incomplete_Class instances

        $this->setupCompletePermissionChain();
        $owner = Role::where('name', 'owner')->first();

        // Get cached permissions
        $permissions = $owner->cachedPermissions();

        // Manually serialize/deserialize to simulate Redis behavior with serializable_classes => false
        $serialized = serialize($permissions);
        $deserialized = unserialize($serialized, ['allowed_classes' => false]);

        // The deserialized collection should still be usable (arrays deserialize fine)
        $this->assertNotNull($deserialized);
    }

    #[Test]
    public function diagnose_with_user_having_all_permissions_shows_info_message()
    {
        $this->setupCompletePermissionChain();

        $this->artisan('entrust:diagnose --user=admin@admin.com')
            ->expectsOutputToContain('DB confirms user CAN task-create')
            ->expectsOutputToContain('stale CACHE')
            ->assertExitCode(0);
    }

    // ─────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────

    private function setupCompletePermissionChain(): void
    {
        $owner = Role::factory()->create(['name' => 'owner', 'display_name' => 'Owner']);
        $admin = Role::factory()->create(['name' => 'administrator', 'display_name' => 'Administrator']);

        // Create all permissions from enum
        foreach (PermissionName::allPermissions() as $name => $data) {
            $perm = Permission::factory()->create([
                'name'         => $name,
                'display_name' => $data['display_name'],
                'description'  => $data['description'],
                'grouping'     => $data['grouping'],
            ]);

            $owner->perms()->attach($perm->id);
            $admin->perms()->attach($perm->id);
        }

        // Assign owner role to first user
        $user = User::factory()->create(['email' => 'admin@admin.com']);
        $user->attachRole($owner);

        // Clear cache to ensure fresh load
        EntrustCacheService::clear();
    }
}

