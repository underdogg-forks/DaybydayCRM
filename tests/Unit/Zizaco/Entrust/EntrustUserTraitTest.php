<?php

namespace Tests\Unit\Zizaco\Entrust;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('entrust-traits')]
class EntrustUserTraitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_returns_true_for_allowed_permissions()
    {
        $role = Role::factory()->create(['name' => 'editor']);
        $user = User::factory()->create();

        $perm1 = Permission::factory()->create(['name' => 'can-edit']);
        $perm2 = Permission::factory()->create(['name' => 'can-delete']);

        $role->perms()->attach([$perm1->id, $perm2->id]);
        $user->attachRole($role);

        $this->assertTrue($user->can('can-edit'));
        $this->assertTrue($user->can('can-delete'));
    }

    #[Test]
    public function user_can_returns_false_for_denied_permissions()
    {
        $role = Role::factory()->create(['name' => 'viewer']);
        $user = User::factory()->create();

        $perm = Permission::factory()->create(['name' => 'can-view']);
        Permission::factory()->create(['name' => 'can-edit']);

        $role->perms()->attach($perm->id);
        $user->attachRole($role);

        $this->assertTrue($user->can('can-view'));
        $this->assertFalse($user->can('can-edit'));
    }

    #[Test]
    public function user_can_checks_multiple_permissions()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create();

        $perm1 = Permission::factory()->create(['name' => 'create-post']);
        $perm2 = Permission::factory()->create(['name' => 'edit-post']);
        Permission::factory()->create(['name' => 'delete-post']);

        $role->perms()->attach([$perm1->id, $perm2->id]);
        $user->attachRole($role);

        // Any match (requireAll = false)
        $this->assertTrue($user->can(['create-post', 'delete-post'], false));
        $this->assertTrue($user->can(['create-post', 'edit-post'], false));

        // All required (requireAll = true)
        $this->assertTrue($user->can(['create-post', 'edit-post'], true));
        $this->assertFalse($user->can(['create-post', 'delete-post'], true));
    }

    #[Test]
    public function user_can_with_wildcard_permissions()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create();

        $perm1 = Permission::factory()->create(['name' => 'post-create']);
        $perm2 = Permission::factory()->create(['name' => 'post-edit']);

        $role->perms()->attach([$perm1->id, $perm2->id]);
        $user->attachRole($role);

        // Wildcard matching
        $this->assertTrue($user->can('post-*'));
        $this->assertFalse($user->can('user-*'));
    }

    #[Test]
    public function user_with_multiple_roles_collects_all_permissions()
    {
        $editorRole = Role::factory()->create(['name' => 'editor']);
        $moderatorRole = Role::factory()->create(['name' => 'moderator']);
        $user = User::factory()->create();

        $editPerm = Permission::factory()->create(['name' => 'can-edit']);
        $modPerm = Permission::factory()->create(['name' => 'can-moderate']);
        $deletePerm = Permission::factory()->create(['name' => 'can-delete']);

        $editorRole->perms()->attach($editPerm->id);
        $moderatorRole->perms()->attach([$modPerm->id, $deletePerm->id]);

        $user->attachRole($editorRole);
        $user->attachRole($moderatorRole);

        // User should have all permissions from both roles
        $this->assertTrue($user->can('can-edit'));
        $this->assertTrue($user->can('can-moderate'));
        $this->assertTrue($user->can('can-delete'));
    }

    #[Test]
    public function user_without_roles_cannot_access_anything()
    {
        $user = User::factory()->create();

        Permission::factory()->create(['name' => 'can-view']);
        Permission::factory()->create(['name' => 'can-edit']);

        $this->assertFalse($user->can('can-view'));
        $this->assertFalse($user->can('can-edit'));
    }

    #[Test]
    public function user_with_empty_role_cannot_access_permissions()
    {
        $emptyRole = Role::factory()->create(['name' => 'guest']);
        $user = User::factory()->create();

        Permission::factory()->create(['name' => 'can-view']);

        $user->attachRole($emptyRole);

        $this->assertFalse($user->can('can-view'));
    }

    #[Test]
    public function has_role_returns_true_for_assigned_roles()
    {
        $editorRole = Role::factory()->create(['name' => 'editor']);
        $moderatorRole = Role::factory()->create(['name' => 'moderator']);
        $user = User::factory()->create();

        $user->attachRole($editorRole);

        $this->assertTrue($user->hasRole('editor'));
        $this->assertFalse($user->hasRole('moderator'));
    }

    #[Test]
    public function has_role_with_multiple_roles()
    {
        $role1 = Role::factory()->create(['name' => 'role1']);
        $role2 = Role::factory()->create(['name' => 'role2']);
        $role3 = Role::factory()->create(['name' => 'role3']);
        $user = User::factory()->create();

        $user->attachRole($role1);
        $user->attachRole($role2);

        // Any match
        $this->assertTrue($user->hasRole(['role1', 'role3'], false));
        $this->assertTrue($user->hasRole(['role1', 'role2'], false));

        // All required
        $this->assertTrue($user->hasRole(['role1', 'role2'], true));
        $this->assertFalse($user->hasRole(['role1', 'role3'], true));
    }

    #[Test]
    public function attach_role_adds_to_cached_roles()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create();

        $this->assertFalse($user->hasRole('admin'));

        $user->attachRole($role);

        $this->assertTrue($user->hasRole('admin'));
    }

    #[Test]
    public function detach_role_removes_from_cached_roles()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create();

        $user->attachRole($role);
        $this->assertTrue($user->hasRole('admin'));

        $user->detachRole($role);

        $this->assertFalse($user->hasRole('admin'));
    }

    #[Test]
    public function ability_checks_both_roles_and_permissions()
    {
        $role = Role::factory()->create(['name' => 'admin']);
        $user = User::factory()->create();

        $perm1 = Permission::factory()->create(['name' => 'can-delete']);
        $role->perms()->attach($perm1->id);
        $user->attachRole($role);

        $result = $user->ability('admin', 'can-delete', ['return_type' => 'array']);

        $this->assertTrue($result['roles']['admin']);
        $this->assertTrue($result['permissions']['can-delete']);
    }

    #[Test]
    public function user_can_calls_cached_permissions_on_roles()
    {
        // This test verifies that the fix for cachedPermissions works correctly
        // by ensuring user->can() properly uses role->cachedPermissions()

        $role = Role::factory()->create(['name' => 'checker']);
        $user = User::factory()->create();

        $perm = Permission::factory()->create(['name' => 'special-action']);
        $role->perms()->attach($perm->id);
        $user->attachRole($role);

        // Call can() multiple times to ensure caching works
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($user->can('special-action'), "Iteration $i failed");
        }

        // Also verify hasRole works with cached roles
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($user->hasRole('checker'), "hasRole iteration $i failed");
        }
    }
}

