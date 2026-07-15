<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('cover_photo_path')->nullable(); // Guarda a imagem principal

            // Preços e Descontos
            $table->decimal('price', 10, 2);
            $table->decimal('promotional_price', 10, 2)->nullable();
            $table->integer('discount_pix')->default(0);

            // Dimensões e Peso (Cálculo de frete)
            $table->decimal('weight', 8, 3)->default(0.000); // Em Kg (ex: 0.500 para 500g)
            $table->decimal('height', 10, 2)->default(0);    // Em cm
            $table->decimal('width', 10, 2)->default(0);     // Em cm
            $table->decimal('length', 10, 2)->default(0);    // Em cm

            // Diagrama do produto (PDF)
            $table->string('diagram')->nullable();

            // Controle e Produção
            $table->integer('stock');
            $table->integer('days_to_create');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
