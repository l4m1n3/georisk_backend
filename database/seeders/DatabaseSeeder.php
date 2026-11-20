<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CommuneSeeder::class,
            NeighborhoodSeeder::class,
            UserSeeder::class,
            ReportSeeder::class,
        ]);
    }
}
