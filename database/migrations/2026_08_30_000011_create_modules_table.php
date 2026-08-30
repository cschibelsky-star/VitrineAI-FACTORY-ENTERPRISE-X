<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('name', 200);
            $table->string('slug', 120)->unique();
            $table->string('version', 30)->default('1.0.0');
            $table->json('requires')->nullable();
            $table->json('optional_integrations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
