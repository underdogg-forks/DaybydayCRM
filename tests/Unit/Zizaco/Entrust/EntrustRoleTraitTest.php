<?php

namespace Tests\Unit\Zizaco\Entrust;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('entrust-traits')]
class EntrustRoleTraitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cached_permissions_returns_permission_objects()
    {
        $role = Role::factory()->create(['name' => 'test-role']);

        $permissions = Permission::factory()->createMany([
            ['name' => 'perm-1'],
            ['name' => 'perm-2'],
            ['name' => 'perm-3'],
        ]);

        foreach ($permissions as $perm) {
            $role->perms()->attach($perm->id);
        }

        $cachedPerms = $role->cachedPermissions();

        $this->assertCount(3, $cachedPerms);

        foreach ($cachedPerms as $perm) {
            $this->assertIsObject($perm);
            $this->assertNotNull($perm->name);
            $this->assertIsString($perm->name);
        }
    }

    #[Test]
    public function has_permission_works_with_cached_permissions()
    {
        $role = Role::factory()->create(['name' => 'test-role']);

        $perm1 = Permission::factory()->create(['name' => 'can-edit']);
        $perm2 = Permission::factory()->create(['name' => 'can-delete']);

        $role->perms()->attach($perm1->id);

        $this->assertTrue($role->hasPermission('can-edit'));
        $this->assertFalse($role->hasPermission('can-delete'));
    }

    #[Test]
    public function has_permission_with_array_of_permissions()
    {
        $role = Role::factory()->create(['name' => 'test-role']);

        $perm1 = Permission::factory()->create(['name' => 'can-view']);
        $perm2 = Permission::factory()->create(['name' => 'can-edit']);

        $role->perms()->attach([$perm1->id, $perm2->id]);

        $this->assertTrue($role->hasPermission(['can-view', 'can-edit'], true));
        $this->assertFalse($role->hasPermission(['can-view', 'can-delete'], true));
        $this->assertTrue($role->hasPermission(['can-view', 'can-delete'], false));
    }

    #[Test]
    public function cached_permissions_returns_empty_collection_when_no_permissions()
    {
        $role = Role::factory()->create(['name' => 'empty-role']);

        $cachedPerms = $role->cachedPermissions();

        $this->assertEmpty($cachedPerms);
    }

    #[Test]
    public function attaching_permission_flushes_cache()
    {
        $role = Role::factory()->create(['name' => 'test-role']);
        $perm1 = Permission::factory()->create(['name' => 'perm-1']);
        $perm2 = Permission::factory()->create(['name' => 'perm-2']);

        // Get initial cached permissions
        $initial = $role->cachedPermissions();
        $this->assertEmpty($initial);

        // Attach a permission
        $role->attachPermission($perm1->id);

        // Cache should be cleared, so new fetch should include the permission
        $updated = $role->cachedPermissions();
        $this->assertCount(1, $updated);
        $this->assertEquals('perm-1', $updated->first()->name);

        // Attach another
        $role->attachPermission($perm2->id);
        $final = $role->cachedPermissions();
        $this->assertCount(2, $final);
    }

    #[Test]
    public function detaching_permission_flushes_cache()
    {
        $role = Role::factory()->create(['name' => 'test-role']);
        $perm1 = Permission::factory()->create(['name' => 'perm-1']);
        $perm2 = Permission::factory()->create(['name' => 'perm-2']);

        $role->perms()->attach([$perm1->id, $perm2->id]);
        $this->assertCount(2, $role->cachedPermissions());

        $role->detachPermission($perm1->id);
        $updated = $role->cachedPermissions();
        $this->assertCount(1, $updated);
        $this->assertEquals('perm-2', $updated->first()->name);
    }

    #[Test]
    public function save_permissions_updates_cache()
    {
        $role = Role::factory()->create(['name' => 'test-role']);

        $perm1 = Permission::factory()->create(['name' => 'perm-1']);
        $perm2 = Permission::factory()->create(['name' => 'perm-2']);
        $perm3 = Permission::factory()->create(['name' => 'perm-3']);

        $role->savePermissions([$perm1->id, $perm2->id]);
        $cached = $role->cachedPermissions();
        $this->assertCount(2, $cached);

        $role->savePermissions([$perm1->id, $perm3->id]);
        $updated = $role->cachedPermissions();
        $this->assertCount(2, $updated);

        $names = $updated->pluck('name')->toArray();
        $this->assertContains('perm-1', $names);
        $this->assertContains('perm-3', $names);
    }

    #[Test]
    public function attach_permissions_multiple_uses_sync_without_detaching()
    {
        $role = Role::factory()->create(['name' => 'test-role']);

        $perms = Permission::factory()->createMany([
            ['name' => 'perm-1'],
            ['name' => 'perm-2'],
            ['name' => 'perm-3'],
        ]);

        // Attach first two
        $role->attachPermissions([$perms[0]->id, $perms[1]->id]);
        $this->assertCount(2, $role->cachedPermissions());

        // Attach with overlap - should not create duplicates
        $role->attachPermissions([$perms[1]->id, $perms[2]->id]);
        $cached = $role->cachedPermissions();
        $this->assertCount(3, $cached);

        // Verify no duplicates in database
        $this->assertEquals(3, $role->perms()->count());
    }
}

