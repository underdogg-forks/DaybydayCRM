<?php

namespace Tests\Unit\Services\User;

use App\Enums\RoleType;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\User\UserUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[CoversClass(UserUpdateService::class)]
class UserUpdateServiceTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_password_fields_when_actor_cannot_change_password(): void
    {
        // Arrange
        $service = new UserUpdateService();
        $actor = User::factory()->withRole('employee')->create();
        $target = User::factory()->withRole('employee')->create();

        // Act
        $payload = $service->prepareValidatedInput($actor, $target, [
            'name' => 'Updated',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], null);

        // Assert
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('password_confirmation', $payload);
    }

    #[Test]
    public function it_hashes_password_when_actor_can_change_password(): void
    {
        // Arrange
        $service = new UserUpdateService();
        $actor = User::factory()->withRole('owner')->create();
        $target = User::factory()->withRole('employee')->create();

        // Act
        $payload = $service->prepareValidatedInput($actor, $target, [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], null);

        // Assert
        $this->assertTrue(password_verify('secret123', $payload['password']));
    }

    #[Test]
    public function it_prevents_changing_last_owner_role(): void
    {
        // Arrange
        $service = new UserUpdateService();
        $owner = User::factory()->withRole('owner')->create();
        $newRole = Role::factory()->create(['name' => 'employee', 'display_name' => 'Employee']);
        $department = Department::factory()->create();

        // Act
        $result = $service->syncRoleAndDepartment($owner, $owner, $newRole->id, $department->id);

        // Assert
        $this->assertFalse($result);
        $this->assertSame(RoleType::OWNER->value, $owner->fresh()->roles->first()->name);
    }
}
