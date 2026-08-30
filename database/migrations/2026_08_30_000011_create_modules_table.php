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
            $table->string('key', 120)->unique();
            $table->string('name', 200);
            $table->string('version', 30);
            $table->string('status', 30)->default('available');
            $table->json('requires')->nullable();
            $table->json('optional_integrations')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
