<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add basic extra columns to users table (no FK to communes yet —
 * the FK is added in a later migration after communes table exists).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('name');
            $table->string('role')->default('citizen')->after('password');
            $table->unsignedBigInteger('commune_id')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('commune_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'commune_id', 'is_active']);
        });
    }
};
