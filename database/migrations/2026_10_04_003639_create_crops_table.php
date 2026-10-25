<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crops', function (Blueprint $table) {
            $table->id();

            $table->foreignId('land_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('variety')->nullable();

            $table->date('planting_date');
            $table->date('estimated_harvest_date')->nullable();

            $table->enum('status', [
                'persiapan',
                'ditanam',
                'perawatan',
                'siap_panen',
                'selesai'
            ])->default('ditanam');

            $table->decimal('seed_quantity', 10, 2)->nullable();
            $table->string('seed_unit')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};
