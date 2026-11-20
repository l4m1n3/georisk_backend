<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_history', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // risk_zone | drainage_structure | report
            $table->uuid('entity_id');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_history');
    }
};
