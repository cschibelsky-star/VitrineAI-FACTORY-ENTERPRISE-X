<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimento_cases', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id');
            $table->ulid('person_id')->index();
            $table->ulid('responsible_user_id')->nullable()->index();
            $table->string('subject', 180);
            $table->string('status', 40)->default('open');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('atendimento_notes', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id');
            $table->ulid('case_id');
            $table->ulid('author_user_id')->nullable()->index();
            $table->text('body');
            $table->timestamps();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->foreign('case_id')->references('ulid')->on('atendimento_cases')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimento_notes');
        Schema::dropIfExists('atendimento_cases');
    }
};
