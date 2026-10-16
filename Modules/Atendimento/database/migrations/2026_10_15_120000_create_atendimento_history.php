<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimento_history', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id');
            $table->ulid('case_id');
            $table->ulid('actor_user_id');
            $table->string('action', 40);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->foreign('case_id')->references('ulid')->on('atendimento_cases')->cascadeOnDelete();
            $table->index(['tenant_id', 'case_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('atendimento_history'); }
};
