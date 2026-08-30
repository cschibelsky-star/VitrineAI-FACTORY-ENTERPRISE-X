<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadastro_guardian_relations', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('person_id')->index();
            $table->ulid('guardian_person_id')->index();
            $table->string('relationship', 80)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('can_authorize')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'person_id', 'guardian_person_id'], 'cadastro_guardian_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastro_guardian_relations');
    }
};
