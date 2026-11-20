<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drainage_structures', function (Blueprint $table) {
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

            $table->string('structure_type'); // StructureType enum
            $table->string('condition');      // StructureCondition enum
            $table->string('material')->nullable();

            $table->date('last_maintenance_date')->nullable();

            $table->string('photo_url')->nullable();

            $table->string('validation_status')
                ->default('pending');

            $table->text('comment')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drainage_structures');
    }
};
