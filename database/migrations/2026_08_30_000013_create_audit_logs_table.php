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
            $table->string('module_key', 120)->nullable()->index();
            $table->string('action', 120);
            $table->string('entity_type', 200);
            $table->string('entity_id', 64)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
