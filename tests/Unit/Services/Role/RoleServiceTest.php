<?php

namespace Tests\Unit\Services\Role;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Role\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[CoversClass(RoleService::class)]
class RoleServiceTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_covers_role_service_methods(): void
    {
        $service = new RoleService();

        $role = $service->create(['name' => 'manager', 'description' => 'desc']);
        $p1 = Permission::factory()->create();
        $p2 = Permission::factory()->create();

        $service->syncPermissions($role, [$p1->id => '1', $p2->id => '0']);

        $this->assertCount(1, $role->fresh()->permissions);

        $blocked = Role::factory()->create(['name' => Role::ADMIN_ROLE]);
        $normal = Role::factory()->create(['name' => 'custom']);

        $this->assertFalse($service->destroy($blocked));
        $this->assertTrue($service->destroy($normal));
    }
}
