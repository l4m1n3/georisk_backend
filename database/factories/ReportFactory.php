<?php

namespace Database\Factories;

use App\Models\Commune;
use App\Models\Neighborhood;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $isAnonymous = fake()->boolean(30);

        return [
            'category' => fake()->randomElement(['risk_zone', 'drainage_structure']),
            'latitude' => fake()->randomFloat(7, 13.44, 13.58),
            'longitude' => fake()->randomFloat(7, 2.02, 2.18),
            'gps_accuracy' => fake()->randomFloat(2, 3, 30),
            'severity' => fake()->numberBetween(1, 5),
            'commune_id' => Commune::factory(),
            'neighborhood_id' => Neighborhood::factory(),
            'collector_type' => $isAnonymous ? 'anonymous' : 'user',
            'collector_id' => $isAnonymous ? null : User::factory(),
            'photo_url' => fake()->boolean(65) ? '/storage/reports/'.Str::uuid().'.jpg' : null,
            'validation_status' => 'pending',
            'comment' => fake()->optional(0.7)->sentence(10),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['validation_status' => 'pending']);
    }

    public function validated(): static
    {
        return $this->state(fn () => ['validation_status' => 'validated']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['validation_status' => 'rejected']);
    }

    public function riskZone(): static
    {
        return $this->state(fn () => ['category' => 'risk_zone']);
    }

    public function drainageStructure(): static
    {
        return $this->state(fn () => ['category' => 'drainage_structure']);
    }
}
