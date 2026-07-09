<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Se você gerar um endereço isolado, ele cria um novo usuário automaticamente
            'user_id' => User::factory(),

            'titulo' => $this->faker->randomElement(['Casa', 'Trabalho', 'Casa dos Pais']),
            'destinatario' => $this->faker->name(),
            'telefone' => $this->faker->phoneNumber(), // Formato: (XX) 9XXXX-XXXX
            'cep' => $this->faker->postcode(), // Remove hifens se sua coluna for apenas números
            'logradouro' => $this->faker->streetName(),
            'numero' => $this->faker->buildingNumber(),
            'bairro' => $this->faker->words(2, true),
            'cidade' => $this->faker->city(),
            'estado' => $this->faker->stateAbbr(), // Retorna siglas como RS, SP, SC
            'complemento' => $this->faker->optional(0.6)->secondaryAddress(), // 60% de chance de ter algo como "Apto 302" ou "Bloco B"
            'padrao' => $this->faker->boolean(0),
            ];
    }
}
