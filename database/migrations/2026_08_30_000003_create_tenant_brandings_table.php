<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_brandings', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->unique();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->string('system_name', 200)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('favicon_path', 500)->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('accent_color', 20)->nullable();
            $table->text('document_footer')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_brandings');
    }
};
