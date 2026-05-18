<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\WorldBuilder;
use Illuminate\Database\Seeder;

/**
 * DummyDatabaseSeeder
 *
 * Lean, deterministic dataset for Playwright / CI / PHPUnit.
 * Previously called the individual Dummy\* seeders separately.
 * Now uses the shared WorldBuilder trait with $sparse = true for speed.
 *
 * Run with:  php artisan db:seed --class=DummyDatabaseSeeder
 *
 * Well-known credentials (hard-coded so tests never break):
 *   owner@test.local    / password
 *   admin@test.local    / password
 *   manager@test.local  / password
 *   employee@test.local / password
 */
class DummyDatabaseSeeder extends Seeder
{
    use WorldBuilder;

    /**
     * Stable credentials – mirror these in your Playwright fixtures file.
     * @see playwright/fixtures/users.ts
     */
    public const USERS = [
        ['name' => 'Test Owner',    'email' => 'owner@test.local',    'password' => 'password', 'role' => 'owner'],
        ['name' => 'Test Admin',    'email' => 'admin@test.local',    'password' => 'password', 'role' => 'administrator'],
        ['name' => 'Test Manager',  'email' => 'manager@test.local',  'password' => 'password', 'role' => 'manager'],
        ['name' => 'Test Employee', 'email' => 'employee@test.local', 'password' => 'password', 'role' => 'employee'],
    ];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('  ── Dummy / Test data ─────────────────────');

        $this->call(CoreSeeder::class);

        $steps = ['Products', 'Users', 'Clients & relations'];
        $bar   = $this->command->getOutput()->createProgressBar(count($steps));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->start();

        // 1. Products (small fixed set so IDs are predictable)
        $bar->setMessage('Products');
        $this->call(ProductSeeder::class);
        $bar->advance();

        // 2. Named users with stable credentials + a few factory users
        $bar->setMessage('Users');
        $users = $this->createUsers(
            perRole: [
                'manager'  => 2,
                'employee' => 3,
            ],
            namedUsers: self::USERS,
        );
        $bar->advance();

        // 3. Slim relational tree
        $bar->setMessage('Clients & relations');
        $this->createClientTree(
            users:             $users,
            clientsPerUser:    2,
            projectsPerClient: 2,
            tasksPerClient:    4,
            leadsPerClient:    3,
            commentsPerItem:   2,
            sparse:            true,
        );
        $bar->advance();

        $bar->setMessage('Done ✓');
        $bar->finish();
        $this->command->info('');
        $this->command->info('');
        $this->command->info('Test credentials (all passwords: "password"):');
        $this->command->table(
            ['Role', 'Email'],
            array_map(fn ($u) => [$u['role'], $u['email']], self::USERS)
        );
    }
}
