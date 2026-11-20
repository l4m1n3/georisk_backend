<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReportSeeder extends Seeder
{
    /**
     * Nombre total de signalements visé, aligné sur l'objectif de la
     * collecte pilote du projet (20 à 30 signalements réels).
     */
    private int $target = 25;

    public function run(): void
    {
        $communes = Commune::with('neighborhoods')->get()->keyBy('code');
        $citizens = User::where('role', 'citizen')->get();

        if ($communes->isEmpty() || $citizens->isEmpty()) {
            $this->command?->warn(
                'Aucune commune ou aucun citoyen trouvé. '
                .'Lancez CommuneSeeder, NeighborhoodSeeder et UserSeeder avant ReportSeeder.'
            );

            return;
        }

        $curatedCount = $this->seedCuratedReports($communes, $citizens);
        $randomCount = $this->seedRandomReports($communes, $citizens, max(0, $this->target - $curatedCount));

        $this->command?->info(sprintf(
            '%d signalements créés (%d réalistes + %d aléatoires).',
            $curatedCount + $randomCount,
            $curatedCount,
            $randomCount
        ));
    }

    /**
     * Quelques signalements écrits à la main, avec des commentaires réalistes,
     * utiles pour une démonstration ou une capture d'écran du dashboard.
     */
    private function seedCuratedReports($communes, $citizens): int
    {
        $curated = [
            [
                'commune' => 'NY-02', 'neighborhood' => 'Terminus',
                'category' => 'risk_zone', 'severity' => 5,
                'lat' => 13.5137, 'lng' => 2.1215, 'gps_accuracy' => 6.2,
                'validation_status' => 'validated',
                'comment' => "Stagnation d'eau depuis 3 jours après les pluies, hauteur estimée à 40 cm devant le marché.",
                'photo' => true, 'days_ago' => 6,
            ],
            [
                'commune' => 'NY-04', 'neighborhood' => 'Talladjé',
                'category' => 'drainage_structure', 'severity' => 4,
                'lat' => 13.5309, 'lng' => 2.1523, 'gps_accuracy' => 9.8,
                'validation_status' => 'pending',
                'comment' => 'Caniveau bouché par des déchets plastiques sur environ 15 mètres.',
                'photo' => true, 'days_ago' => 2,
            ],
            [
                'commune' => 'NY-01', 'neighborhood' => 'Kirkissoye',
                'category' => 'risk_zone', 'severity' => 3,
                'lat' => 13.5061, 'lng' => 2.0872, 'gps_accuracy' => 12.4,
                'validation_status' => 'pending',
                'comment' => "Zone régulièrement inondée en début de saison des pluies, proche de l'école primaire.",
                'photo' => false, 'days_ago' => 1,
            ],
            [
                'commune' => 'NY-03', 'neighborhood' => 'Gamkalley',
                'category' => 'drainage_structure', 'severity' => 5,
                'lat' => 13.4944, 'lng' => 2.1187, 'gps_accuracy' => 5.1,
                'validation_status' => 'rejected',
                'comment' => 'Signalement en doublon avec un ouvrage déjà répertorié la semaine dernière.',
                'photo' => false, 'days_ago' => 9,
            ],
            [
                'commune' => 'NY-05', 'neighborhood' => 'Saga',
                'category' => 'risk_zone', 'severity' => 4,
                'lat' => 13.4801, 'lng' => 2.0523, 'gps_accuracy' => 15.0,
                'validation_status' => 'pending',
                'comment' => 'Digue affaiblie côté fleuve, risque de rupture en cas de forte crue.',
                'photo' => true, 'days_ago' => 3,
            ],
            [
                'commune' => 'NY-02', 'neighborhood' => 'Boukoki',
                'category' => 'drainage_structure', 'severity' => 2,
                'lat' => 13.5183, 'lng' => 2.1092, 'gps_accuracy' => 8.7,
                'validation_status' => 'validated',
                'comment' => 'Regard partiellement obstrué, écoulement encore possible mais ralenti.',
                'photo' => true, 'days_ago' => 5,
            ],
        ];

        foreach ($curated as $data) {
            $commune = $communes->get($data['commune']);

            if (! $commune) {
                continue;
            }

            $neighborhood = $commune->neighborhoods->firstWhere('name', $data['neighborhood']);
            $collector = $citizens->random();

            Report::create([
                'category' => $data['category'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'gps_accuracy' => $data['gps_accuracy'],
                'severity' => $data['severity'],
                'commune_id' => $commune->id,
                'neighborhood_id' => $neighborhood?->id,
                'collector_type' => 'user',
                'collector_id' => $collector->id,
                'photo_url' => $data['photo'] ? '/storage/reports/'.Str::uuid().'.jpg' : null,
                'validation_status' => $data['validation_status'],
                'comment' => $data['comment'],
                'created_at' => now()->subDays($data['days_ago']),
                'updated_at' => now()->subDays(max(0, $data['days_ago'] - 1)),
            ]);
        }

        return count($curated);
    }

    /**
     * Complète avec des signalements générés aléatoirement mais géographiquement
     * cohérents (quartier toujours rattaché à sa vraie commune).
     */
    private function seedRandomReports($communes, $citizens, int $count): int
    {
        for ($i = 0; $i < $count; $i++) {
            $commune = $communes->random();
            $neighborhood = $commune->neighborhoods->isNotEmpty()
                ? $commune->neighborhoods->random()
                : null;

            $isAnonymous = fake()->boolean(30);
            $daysAgo = fake()->numberBetween(0, 21);

            Report::create([
                'category' => fake()->randomElement(['risk_zone', 'drainage_structure']),
                'latitude' => fake()->randomFloat(7, 13.44, 13.58),
                'longitude' => fake()->randomFloat(7, 2.02, 2.18),
                'gps_accuracy' => fake()->randomFloat(2, 3, 30),
                'severity' => fake()->numberBetween(1, 5),
                'commune_id' => $commune->id,
                'neighborhood_id' => $neighborhood?->id,
                'collector_type' => $isAnonymous ? 'anonymous' : 'user',
                'collector_id' => $isAnonymous ? null : $citizens->random()->id,
                'photo_url' => fake()->boolean(65) ? '/storage/reports/'.Str::uuid().'.jpg' : null,
                'validation_status' => fake()->randomElement(['pending', 'pending', 'pending', 'validated', 'rejected']),
                'comment' => fake()->boolean(70) ? fake()->sentence(10) : null,
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays(max(0, $daysAgo - 1)),
            ]);
        }

        return $count;
    }
}
