<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('provider_type', 80)->nullable();
            $table->string('status', 30)->default('ativo');
            $table->text('api_key')->nullable();
            $table->json('config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
