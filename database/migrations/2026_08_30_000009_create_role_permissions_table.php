<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->ulid('role_id');
            $table->ulid('permission_id');
            $table->foreign('role_id')->references('ulid')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('ulid')->on('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
