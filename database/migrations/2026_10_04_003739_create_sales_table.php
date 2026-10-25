<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('harvest_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('buyer_name');

            $table->date('sale_date');

            $table->decimal('quantity', 10, 2);
            $table->string('unit');

            $table->decimal('price_per_unit', 15, 2);

            $table->decimal('total', 15, 2);

            $table->enum('payment_status', [
                'lunas',
                'belum_lunas'
            ])->default('belum_lunas');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
