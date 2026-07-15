<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Materiais típicos de produtos artesanais e manuais
            'name' => $this->faker->unique()->randomElement([
                'Fio de Algodão',
                'Cerâmica',
                'Palha Natural',
                'Madeira de Reflorestamento',
                'Linho',
                'Lã Natural',
                'Barbante Ecológico',
                'Sisal',
                'Metal Galvanizado',
                'Resina'
            ]),
        ];
    }
}
