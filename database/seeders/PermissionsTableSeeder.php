<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * PermissionName enum is the single source of truth.
 * Adding a permission to the enum is all that is needed.
 */
class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionName::allPermissions() as $name => $data) {
            Permission::query()->firstOrCreate(
                ['name' => $name],
                [
                    'external_id'  => Str::uuid()->toString(),
                    'display_name' => $data['display_name'],
                    'description'  => $data['description'],
                    'grouping'     => $data['grouping'],
                ]
            );
        }
    }
}
