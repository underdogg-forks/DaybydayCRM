<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ClientsTableSeeder extends Seeder
{
    public function run()
    {
        $owner = User::query()->orderBy('id')->first();
        $industry = Industry::query()->orderBy('id')->first();

        if (! $owner || ! $industry) {
            $this->command->warn('ClientsTableSeeder: missing user or industry, skipping.');

            return;
        }

        DB::table('clients')->updateOrInsert(
            ['company_name' => 'Playwright Seed Client'],
            [
                'external_id' => Uuid::uuid4()->toString(),
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
