<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->nullable()->index();
            $table->ulid('user_id')->nullable()->index();
            $table->string('module', 120)->nullable();
            $table->string('action', 120);
            $table->string('entity', 200);
            $table->string('entity_id', 36)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
