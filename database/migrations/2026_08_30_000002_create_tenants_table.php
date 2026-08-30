<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('name', 200);
            $table->string('slug', 120)->unique();
            $table->string('email', 200)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 30)->default('active');
            $table->ulid('plan_id')->nullable();
            $table->foreign('plan_id')->references('ulid')->on('plans')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
