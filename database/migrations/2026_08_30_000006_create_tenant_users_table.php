<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id');
            $table->ulid('user_id');
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('ulid')->on('users')->cascadeOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
