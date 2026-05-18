<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Modes:
     *   php artisan db:seed                              → core only
     *   php artisan db:seed --class=DemoTableSeeder      → core + rich demo data
     *   php artisan db:seed --class=DummyDatabaseSeeder  → core + dummy/test data
     */
    public function run(): void
    {
        $this->call(CoreSeeder::class);
    }
}
