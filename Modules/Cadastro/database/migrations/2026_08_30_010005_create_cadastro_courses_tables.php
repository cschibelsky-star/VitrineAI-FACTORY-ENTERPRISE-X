<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadastro_courses', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('cadastro_course_classes', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('course_id')->index();
            $table->string('name', 180);
            $table->unsignedInteger('capacity')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('ulid')->on('cadastro_courses')->cascadeOnDelete();
        });

        Schema::create('cadastro_enrollments', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('class_id')->index();
            $table->ulid('person_id')->index();
            $table->dateTime('enrolled_at');
            $table->dateTime('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'class_id', 'person_id'], 'cadastro_enrollment_unique');
            $table->foreign('class_id')->references('ulid')->on('cadastro_course_classes')->cascadeOnDelete();
            $table->foreign('person_id')->references('ulid')->on('cadastro_people')->cascadeOnDelete();
        });

        Schema::create('cadastro_waiting_list_entries', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('class_id')->index();
            $table->ulid('person_id')->index();
            $table->unsignedInteger('position');
            $table->dateTime('joined_at');
            $table->dateTime('promoted_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('ulid')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'class_id', 'person_id'], 'cadastro_waiting_unique');
            $table->foreign('class_id')->references('ulid')->on('cadastro_course_classes')->cascadeOnDelete();
            $table->foreign('person_id')->references('ulid')->on('cadastro_people')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastro_waiting_list_entries');
        Schema::dropIfExists('cadastro_enrollments');
        Schema::dropIfExists('cadastro_course_classes');
        Schema::dropIfExists('cadastro_courses');
    }
};
