<?php

namespace Database\Seeders;

use App\Models\Commune;
use Illuminate\Database\Seeder;

class CommuneSeeder extends Seeder
{
    public function run(): void
    {
        $communes = [
            ['name' => 'Niamey I', 'code' => 'NY-01'],
            ['name' => 'Niamey II', 'code' => 'NY-02'],
            ['name' => 'Niamey III', 'code' => 'NY-03'],
            ['name' => 'Niamey IV', 'code' => 'NY-04'],
            ['name' => 'Niamey V', 'code' => 'NY-05'],
        ];

        foreach ($communes as $commune) {
            Commune::firstOrCreate(['code' => $commune['code']], $commune);
        }
    }
}
