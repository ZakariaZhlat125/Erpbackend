<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            DemoOrganizationSeeder::class,
            ChartOfAccountsSeeder::class,
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
