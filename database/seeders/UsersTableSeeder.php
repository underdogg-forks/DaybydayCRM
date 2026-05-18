<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class UsersTableSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@admin.com';

    /**
     * Seeds the single admin user that every environment needs.
     * Uses firstOrCreate so re-seeding is safe.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'external_id'      => Uuid::uuid4()->toString(),
                'name'             => 'Admin',
                'password'         => bcrypt('admin123'),
                'address'          => '',
                'primary_number'   => null,
                'secondary_number' => null,
                'image_path'       => '',
            ]
        );
    }
}
