<?php

namespace App\Services\Role;

use App\Models\Role;
use Ramsey\Uuid\Uuid;

class RoleService
{
    public function create(array $validated): Role
    {
        $name = mb_strtolower($validated['name']);

        return Role::create([
            'external_id'  => Uuid::uuid4()->toString(),
            'name'         => $name,
            'display_name' => ucfirst($name),
            'description'  => $validated['description'],
        ]);
    }

    public function syncPermissions(Role $role, array $permissions): void
    {
        $allowedPermissions = [];

        foreach ($permissions as $permissionId => $permission) {
            if ($permission === '1') {
                $allowedPermissions[] = (int) $permissionId;
            }
        }

        $role->permissions()->sync($allowedPermissions);
        $role->save();
    }

    public function destroy(Role $role): bool
    {
        if (! $role->users->isEmpty()) {
            return false;
        }

        if ($role->name === Role::ADMIN_ROLE || $role->name === Role::OWNER_ROLE) {
            return false;
        }

        $role->delete();

        return true;
    }
}
