<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'email' => $this->faker->unique()->safeEmail(),
            'telefone' => $this->faker->numerify('###########'),
            'renda_mensal' => $this->faker->randomFloat(2, 1500, 20000),
        ];
    }
}
