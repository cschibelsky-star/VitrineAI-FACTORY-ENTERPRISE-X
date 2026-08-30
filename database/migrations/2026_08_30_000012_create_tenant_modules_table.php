<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id');
            $table->ulid('module_id');
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->foreign('module_id')->references('ulid')->on('modules')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};
