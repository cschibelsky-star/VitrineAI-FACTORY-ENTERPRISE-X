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
            $table->string('version', 30)->default('1.0.0');
            $table->boolean('is_active')->default(true);
            $table->json('dependencies')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
