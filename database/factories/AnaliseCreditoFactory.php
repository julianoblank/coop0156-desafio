<?php

namespace Database\Factories;

use App\Enums\StatusAnalise;
use App\Enums\TipoCredito;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnaliseCredito>
 */
class AnaliseCreditoFactory extends Factory
{
    protected $model = AnaliseCredito::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'nome' => $this->faker->name(),
            'renda_mensal' => $this->faker->randomFloat(2, 1500, 20000),
            'tipo_credito' => $this->faker->randomElement(TipoCredito::cases())->value,
            'valor_solicitado' => $this->faker->randomFloat(2, 1000, 50000),
            'status' => StatusAnalise::PENDENTE,
            'score' => null,
            'taxa_juros' => null,
            'valor_parcela' => null,
            'motivo_rejeicao' => null,
        ];
    }
}
