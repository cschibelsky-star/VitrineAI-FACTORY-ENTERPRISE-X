<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->string('execution_type', 100)->nullable();
            $table->string('status', 30)->default('sucesso');
            $table->decimal('duration_seconds', 10, 3)->nullable();
            $table->decimal('estimated_cost', 12, 4)->default(0);
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_executions');
    }
};
