<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('type', 100);
            $table->string('severity', 30)->default('baixa');
            $table->string('status', 30)->default('aberto');
            $table->string('title', 180);
            $table->text('message')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_alerts');
    }
};
