<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('commune_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('neighborhood_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Coordonnées GPS
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('risk_level'); // RiskLevel enum

            $table->integer('historical_frequency')
                ->nullable()
                ->comment('Number of flood occurrences');

            $table->date('last_flood_date')->nullable();

            $table->integer('water_stagnation_duration')
                ->nullable()
                ->comment('Duration in hours');

            $table->integer('estimated_exposed_population')->nullable();

            $table->string('main_cause')->nullable(); // MainCause enum

            $table->string('photo_url')->nullable();

            $table->string('validation_status')
                ->default('pending'); // ValidationStatus enum

            $table->text('comment')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        // // Add PostGIS geometry column and spatial index
        // DB::statement('ALTER TABLE risk_zones ADD COLUMN geometry geometry(Polygon, 4326) NOT NULL');
        // DB::statement('CREATE INDEX risk_zones_geometry_gist ON risk_zones USING GIST (geometry)');
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_zones');
    }
};
