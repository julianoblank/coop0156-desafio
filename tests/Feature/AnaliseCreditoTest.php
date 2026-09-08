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
}
