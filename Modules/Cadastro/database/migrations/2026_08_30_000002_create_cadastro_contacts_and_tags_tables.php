<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadastro_contacts', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('person_id')->index();
            $table->string('type', 40);
            $table->string('value', 255);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'person_id', 'type', 'value'], 'cadastro_contacts_unique');
        });

        Schema::create('cadastro_tags', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('name', 120);
            $table->string('slug', 120);
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('cadastro_person_tag', function (Blueprint $table) {
            $table->ulid('tenant_id')->index();
            $table->ulid('person_id');
            $table->ulid('tag_id');
            $table->timestamps();
            $table->primary(['tenant_id', 'person_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastro_person_tag');
        Schema::dropIfExists('cadastro_tags');
        Schema::dropIfExists('cadastro_contacts');
    }
};
