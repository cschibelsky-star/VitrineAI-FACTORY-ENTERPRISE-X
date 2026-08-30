<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user_roles', function (Blueprint $table) {
            $table->ulid('tenant_user_id');
            $table->ulid('role_id');
            $table->foreign('tenant_user_id')->references('ulid')->on('tenant_users')->cascadeOnDelete();
            $table->foreign('role_id')->references('ulid')->on('roles')->cascadeOnDelete();
            $table->primary(['tenant_user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_roles');
    }
};
