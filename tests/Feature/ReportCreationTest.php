<?php

use App\Models\Commune;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates a report sent by the mobile application without a neighborhood', function () {
    $user = User::factory()->create();
    $commune = Commune::query()->create([
        'name' => 'Niamey I',
        'code' => 'NI-1',
    ]);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/reports', [
        'category' => 'risk_zone',
        'latitude' => 13.5137,
        'longitude' => 2.1098,
        'gps_accuracy' => 8.5,
        'severity' => 4,
        'commune_id' => $commune->id,
        'comment' => 'Risque constaté près du caniveau.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.comment', 'Risque constaté près du caniveau.')
        ->assertJsonPath('data.neighborhood_id', null);

    $this->assertDatabaseHas('reports', [
        'commune_id' => $commune->id,
        'comment' => 'Risque constaté près du caniveau.',
    ]);
});
