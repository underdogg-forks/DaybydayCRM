<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientsTableSeeder extends Seeder
{
    private const PLAYWRIGHT_CLIENT_NAME = 'Playwright Seed Client';
    private const PLAYWRIGHT_CLIENT_EXTERNAL_ID = '1dcad188-4c47-4939-9f0a-fb6802ef4f0d';

    public function run()
    {
        $owner = User::query()->where('email', 'admin@admin.com')->first();
        $industry = Industry::query()->orderBy('id')->first();

        if (! $owner || ! $industry) {
            $this->command->warn('ClientsTableSeeder: missing seeded admin user or industry, skipping.');

            return;
        }

        DB::table('clients')->updateOrInsert(
            ['company_name' => self::PLAYWRIGHT_CLIENT_NAME],
            [
                'external_id' => self::PLAYWRIGHT_CLIENT_EXTERNAL_ID,
                'address' => 'Seed Street 1',
                'zipcode' => '1000',
                'city' => 'Copenhagen',
                'vat' => '12345678',
                'company_type' => 'ApS',
                'user_id' => $owner->id,
                'industry_id' => $industry->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
