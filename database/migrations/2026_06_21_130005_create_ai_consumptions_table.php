<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('resource_type', 100)->default('execucao');
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('estimated_cost', 12, 4)->default(0);
            $table->date('consumption_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'consumption_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_consumptions');
    }
};
