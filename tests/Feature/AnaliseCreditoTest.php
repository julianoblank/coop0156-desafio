<?php

namespace Tests\Feature;

use App\Enums\StatusAnalise;
use App\Jobs\ProcessarContratacaoJob;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnaliseCreditoTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'João da Silva',
            'cpf' => '12345678903',
            'renda_mensal' => 5000.00,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 10000.00,
        ], $overrides);
    }

    private function fakeBureau(array $body, int $status = 200): void
    {
        Http::fake([
            '*/api/mock/bureau/*' => Http::response($body, $status),
        ]);
    }

    public function test_aprova_analise_com_score_alto_e_taxa_de_2_9_por_cento(): void
    {
        $this->fakeBureau(['score' => 850]);

        $response = $this->postJson('/api/analise-credito', $this->payload(['cpf' => '12345678903']));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::APROVADO->value,
                'score' => 850,
                'taxa_juros' => '2.90',
            ]);

        $this->assertDatabaseHas('analises_credito', [
            'cpf' => '12345678903',
            'status' => StatusAnalise::APROVADO->value,
        ]);
    }

    public function test_aprova_analise_com_score_medio_e_taxa_de_4_5_por_cento(): void
    {
        $this->fakeBureau(['score' => 550]);

        $response = $this->postJson('/api/analise-credito', $this->payload(['cpf' => '12345678902']));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::APROVADO->value,
                'score' => 550,
                'taxa_juros' => '4.50',
            ]);
    }

    public function test_reprova_analise_por_renda_mensal_insuficiente(): void
    {
        $this->fakeBureau(['score' => 850]);

        $response = $this->postJson('/api/analise-credito', $this->payload([
            'cpf' => '12345678903',
            'renda_mensal' => 1000.00,
        ]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::REPROVADO->value,
                'motivo_rejeicao' => 'Renda mínima insuficiente',
            ]);
    }

    public function test_reprova_analise_por_score_muito_baixo(): void
    {
        $this->fakeBureau(['score' => 150]);

        $response = $this->postJson('/api/analise-credito', $this->payload(['cpf' => '12345678901']));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::REPROVADO->value,
                'motivo_rejeicao' => 'Score de crédito muito baixo',
            ]);
    }

    public function test_reprova_analise_por_comprometimento_de_renda_superior_a_30_por_cento(): void
    {
        $this->fakeBureau(['score' => 850]);

        $response = $this->postJson('/api/analise-credito', $this->payload([
            'cpf' => '12345678903',
            'renda_mensal' => 1600.00,
            'valor_solicitado' => 50000.00,
        ]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::REPROVADO->value,
                'motivo_rejeicao' => 'Comprometimento de renda superior a 30%',
            ]);
    }

    public function test_trata_falha_da_api_do_bureau_sem_crash(): void
    {
        $this->fakeBureau(['error' => 'Erro interno'], 500);

        $response = $this->postJson('/api/analise-credito', $this->payload(['cpf' => '12345678904']));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::REPROVADO->value,
            ]);

        $this->assertDatabaseHas('analises_credito', [
            'cpf' => '12345678904',
            'status' => StatusAnalise::REPROVADO->value,
            'score' => null,
        ]);
    }

    public function test_contrata_analise_aprovada_e_despacha_o_job_de_contratacao(): void
    {
        Queue::fake();

        $analise = AnaliseCredito::factory()->create([
            'status' => StatusAnalise::APROVADO,
        ]);

        $response = $this->postJson("/api/analise-credito/{$analise->id}/contratar");

        $response->assertOk()
            ->assertJsonFragment(['status' => StatusAnalise::PROCESSANDO_CONTRATACAO->value]);

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'status' => StatusAnalise::PROCESSANDO_CONTRATACAO->value,
        ]);

        Queue::assertPushed(ProcessarContratacaoJob::class, fn ($job) => $job->analiseId === $analise->id);
    }

    public function test_cria_cliente_automaticamente_ao_solicitar_analise_com_cpf_novo(): void
    {
        $this->fakeBureau(['score' => 850]);

        $this->assertDatabaseMissing('clientes', ['cpf' => '12345678903']);

        $response = $this->postJson('/api/analise-credito', $this->payload(['cpf' => '12345678903']));

        $response->assertCreated();

        $this->assertDatabaseHas('clientes', ['cpf' => '12345678903', 'nome' => 'João da Silva']);

        $cliente = Cliente::where('cpf', '12345678903')->first();
        $this->assertEquals($cliente->id, $response->json('cliente_id'));
    }

    // --- Edge cases ---

    public function test_score_exatamente_400_aprova_na_faixa_de_4_5_por_cento(): void
    {
        $this->fakeBureau(['score' => 400]);

        $response = $this->postJson('/api/analise-credito', $this->payload(['valor_solicitado' => 1000.00, 'renda_mensal' => 15000.00]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::APROVADO->value,
                'score' => 400,
                'taxa_juros' => '4.50',
            ]);
    }

    public function test_score_exatamente_699_ainda_na_faixa_de_4_5_por_cento(): void
    {
        $this->fakeBureau(['score' => 699]);

        $response = $this->postJson('/api/analise-credito', $this->payload(['valor_solicitado' => 1000.00, 'renda_mensal' => 15000.00]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::APROVADO->value,
                'score' => 699,
                'taxa_juros' => '4.50',
            ]);
    }

    public function test_score_exatamente_700_ja_muda_para_faixa_de_2_9_por_cento(): void
    {
        $this->fakeBureau(['score' => 700]);

        $response = $this->postJson('/api/analise-credito', $this->payload(['valor_solicitado' => 1000.00, 'renda_mensal' => 15000.00]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::APROVADO->value,
                'score' => 700,
                'taxa_juros' => '2.90',
            ]);
    }

    public function test_score_exatamente_399_ainda_reprova_por_score_baixo(): void
    {
        $this->fakeBureau(['score' => 399]);

        $response = $this->postJson('/api/analise-credito', $this->payload());

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::REPROVADO->value,
                'motivo_rejeicao' => 'Score de crédito muito baixo',
            ]);
    }

    public function test_renda_mensal_exatamente_1500_nao_reprova_por_renda_insuficiente(): void
    {
        $this->fakeBureau(['score' => 850]);

        $response = $this->postJson('/api/analise-credito', $this->payload([
            'renda_mensal' => 1500.00,
            'valor_solicitado' => 3000.00,
        ]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::APROVADO->value,
            ]);
    }

    public function test_renda_mensal_um_centavo_abaixo_de_1500_reprova_por_renda_insuficiente(): void
    {
        $this->fakeBureau(['score' => 850]);

        $response = $this->postJson('/api/analise-credito', $this->payload([
            'renda_mensal' => 1499.99,
            'valor_solicitado' => 3000.00,
        ]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::REPROVADO->value,
                'motivo_rejeicao' => 'Renda mínima insuficiente',
            ]);
    }

    public function test_parcela_dentro_do_limite_de_30_por_cento_da_renda_aprova(): void
    {
        $this->fakeBureau(['score' => 550]);

        // valor 12000 a 4,5% a.m. em 12x => parcela de R$ 1.540,00 (30% de 5140 = 1542,00)
        $response = $this->postJson('/api/analise-credito', $this->payload([
            'renda_mensal' => 5140.00,
            'valor_solicitado' => 12000.00,
        ]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::APROVADO->value,
                'valor_parcela' => '1540.00',
            ]);
    }

    public function test_parcela_um_centavo_acima_do_limite_de_30_por_cento_da_renda_reprova(): void
    {
        $this->fakeBureau(['score' => 550]);

        // mesma parcela de R$ 1.540,00, mas 30% de 5130 = 1539,00 => passa a reprovar
        $response = $this->postJson('/api/analise-credito', $this->payload([
            'renda_mensal' => 5130.00,
            'valor_solicitado' => 12000.00,
        ]));

        $response->assertCreated()
            ->assertJsonFragment([
                'status' => StatusAnalise::REPROVADO->value,
                'motivo_rejeicao' => 'Comprometimento de renda superior a 30%',
            ]);
    }
}
