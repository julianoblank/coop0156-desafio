<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_cliente_com_dados_validos(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'telefone' => '11999999999',
            'renda_mensal' => 3000.00,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertCreated()
            ->assertJsonFragment(['cpf' => '12345678901', 'email' => 'joao@example.com']);

        $this->assertDatabaseHas('clientes', ['cpf' => '12345678901']);
    }

    public function test_nao_cria_cliente_com_campos_obrigatorios_ausentes(): void
    {
        $response = $this->postJson('/api/clientes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'cpf', 'email', 'renda_mensal']);
    }

    public function test_nao_cria_cliente_com_cpf_duplicado(): void
    {
        Cliente::factory()->create(['cpf' => '12345678901']);

        $payload = [
            'nome' => 'Outro Cliente',
            'cpf' => '12345678901',
            'email' => 'outro@example.com',
            'renda_mensal' => 3000.00,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['cpf']);
    }

    public function test_nao_cria_cliente_com_email_duplicado(): void
    {
        Cliente::factory()->create(['email' => 'joao@example.com']);

        $payload = [
            'nome' => 'Outro Cliente',
            'cpf' => '10987654321',
            'email' => 'joao@example.com',
            'renda_mensal' => 3000.00,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_lista_clientes_paginada(): void
    {
        Cliente::factory()->count(3)->create();

        $response = $this->getJson('/api/clientes');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'current_page', 'total'])
            ->assertJsonCount(3, 'data');
    }

    public function test_exibe_cliente_existente(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->getJson("/api/clientes/{$cliente->id}");

        $response->assertOk()->assertJsonFragment(['id' => $cliente->id]);
    }

    public function test_retorna_404_ao_buscar_cliente_inexistente(): void
    {
        $response = $this->getJson('/api/clientes/999999');

        $response->assertNotFound();
    }

    public function test_atualiza_parcialmente_cliente_existente(): void
    {
        $cliente = Cliente::factory()->create(['nome' => 'Nome Antigo']);

        $response = $this->putJson("/api/clientes/{$cliente->id}", [
            'nome' => 'Nome Atualizado',
        ]);

        $response->assertOk()->assertJsonFragment(['nome' => 'Nome Atualizado']);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nome' => 'Nome Atualizado',
            'cpf' => $cliente->cpf,
            'email' => $cliente->email,
        ]);
    }

    public function test_remove_cliente_existente(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->deleteJson("/api/clientes/{$cliente->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_retorna_404_ao_remover_cliente_inexistente(): void
    {
        $response = $this->deleteJson('/api/clientes/999999');

        $response->assertNotFound();
    }
}
