<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->string('queue_type', 100)->nullable();
            $table->string('reference', 150)->nullable();
            $table->string('status', 30)->default('pendente');
            $table->unsignedInteger('priority')->default(5);
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_queues');
    }
};
