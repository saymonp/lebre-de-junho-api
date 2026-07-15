<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 20, 450); // Preço entre R$ 20,00 e R$ 450,00

        // 30% de chance de o produto ter um preço promocional ativo (e menor que o preço original)
        $hasPromotion = $this->faker->boolean(30);
        $promotionalPrice = $hasPromotion
            ? $this->faker->randomFloat(2, 15, $price - 5)
            : null;

        return [
            'name' => $this->faker->unique()->words(3, true), // Ex: "Cesta de Fio", "Vaso de Barro"
            'description' => $this->faker->paragraph(3),

            // Mídias (Simula URLs do S3 da Lebre de Junho)
            'cover_photo_path' => 'https://s3.amazonaws.com/lebre-de-junho/products/covers/' . $this->faker->uuid() . '.jpg',
            'diagram' => $this->faker->boolean(40)
                ? 'https://s3.amazonaws.com/lebre-de-junho/products/diagrams/' . $this->faker->uuid() . '.pdf'
                : null,

            // Preços e Descontos
            'price' => $price,
            'promotional_price' => $promotionalPrice,
            'discount_pix' => $this->faker->randomElement([0, 5, 10, 15]), // Opções comuns de desconto para PIX

            // Dimensões e Peso (essenciais para o cálculo simulado de frete)
            'weight' => $this->faker->randomFloat(3, 0.050, 3.500), // Entre 50g e 3.5kg
            'height' => $this->faker->randomFloat(2, 5.00, 60.00),  // Altura em cm
            'width' => $this->faker->randomFloat(2, 5.00, 40.00),   // Largura em cm
            'length' => $this->faker->randomFloat(2, 5.00, 40.00),  // Comprimento em cm

            // Controle e Produção
            'stock' => $this->faker->numberBetween(0, 20),
            'days_to_create' => $this->faker->numberBetween(1, 10), // Prazo de produção artesanal
        ];
    }

    /**
     * Estado para forçar um produto sem estoque.
     */
    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => 0,
        ]);
    }
}
