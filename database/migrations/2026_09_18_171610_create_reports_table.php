<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category'); // ReportCategory enum
            // geometry column added via raw SQL below
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('gps_accuracy')->nullable()->comment('Accuracy in meters');
            $table->unsignedTinyInteger('severity')->default(3)->comment('1 (low) to 5 (critical)');
            $table->foreignId('commune_id')->constrained()->restrictOnDelete();
            $table->foreignId('neighborhood_id')->nullable()->constrained()->nullOnDelete();
            $table->string('collector_type')->nullable()->comment('user or anonymous');
            $table->unsignedBigInteger('collector_id')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('validation_status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
