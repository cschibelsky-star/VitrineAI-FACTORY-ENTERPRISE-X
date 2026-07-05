<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('category', 100);
            $table->string('title', 255);
            $table->longText('content')->nullable();
            $table->string('version', 30)->default('1.0');
            $table->string('status', 30)->default('rascunho');
            $table->json('tags')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_memories');
    }
};
