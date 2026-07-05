<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('type', 80)->nullable();
            $table->string('product_scope', 120)->nullable();
            $table->string('version', 30)->default('1.0');
            $table->string('model_name', 120)->nullable();
            $table->string('status', 30)->default('online');
            $table->boolean('is_internal')->default(false);
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};
