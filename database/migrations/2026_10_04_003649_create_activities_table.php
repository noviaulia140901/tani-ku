<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('crop_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('activity_type');

            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();

            $table->string('product_name')->nullable();
            $table->string('dosage')->nullable();

            $table->enum('status', [
                'belum_dilakukan',
                'selesai',
                'dilewati'
            ])->default('belum_dilakukan');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
