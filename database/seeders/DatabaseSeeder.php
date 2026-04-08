<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Metadata & Lookup Tables (Essential)
            RoleSeeder::class,
            MaintenanceRequestTypeSeeder::class,

            // 2. Main Demo Architecture (Master Seeder)
            DemoDataSeeder::class,

            // 3. Optional Enrichment Data
            ChatSeeder::class,
            DemoRatingSeeder::class,
        ]);
    }
}
