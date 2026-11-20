<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Neighborhood;
use Illuminate\Database\Seeder;

class NeighborhoodSeeder extends Seeder
{
    /**
     * Quartiers indicatifs par commune, à ajuster selon le quartier pilote retenu.
     */
    private array $map = [
        'NY-01' => ['Yantala', 'Kirkissoye', 'Recasement'],
        'NY-02' => ['Plateau', 'Terminus', 'Boukoki'],
        'NY-03' => ['Gamkalley', 'Lacouroussou', 'Kalley'],
        'NY-04' => ['Lazaret', 'Talladjé', 'Zongo'],
        'NY-05' => ['Saga', 'Banizoumbou', 'Karadjé'],
    ];

    public function run(): void
    {
        foreach ($this->map as $communeCode => $neighborhoods) {
            $commune = Commune::where('code', $communeCode)->first();

            if (! $commune) {
                continue;
            }

            foreach ($neighborhoods as $name) {
                Neighborhood::firstOrCreate([
                    'commune_id' => $commune->id,
                    'name' => $name,
                ]);
            }
        }
    }
}
