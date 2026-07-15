<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Elementos comuns em e-commerce de artesanato/decoração
            'name' => $this->faker->unique()->randomElement([
                'Decoração',
                'Quarto Infantil',
                'Cozinha',
                'Acessórios',
                'Bolsas',
                'Mesa Posta',
                'Organização',
                'Iluminação',
                'Tapetes',
                'Coleção de Inverno'
            ]),
        ];
    }
}
