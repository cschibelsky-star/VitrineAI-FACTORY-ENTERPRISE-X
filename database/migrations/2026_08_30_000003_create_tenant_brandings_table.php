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
            $table->ulid('tenant_id');
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->string('logo_url', 500)->nullable();
            $table->string('favicon_url', 500)->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->text('print_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_brandings');
    }
};
