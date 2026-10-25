<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lands', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('location');
            $table->decimal('area', 10, 2);
            $table->string('area_unit')->default('m²');
            $table->string('soil_type')->nullable();

            $table->enum('status', [
                'aktif',
                'tidak_aktif',
                'persiapan'
            ])->default('aktif');

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lands');
    }
};
