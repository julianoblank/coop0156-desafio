<?php

namespace Tests\Feature;

use App\Models\AnaliseCredito;
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

    // --- Edge cases adicionais ---

    public function test_nao_cria_cliente_com_cpf_com_menos_de_11_digitos(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '123456789',
            'email' => 'joao@example.com',
            'renda_mensal' => 3000.00,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['cpf']);
    }

    public function test_nao_cria_cliente_com_cpf_nao_numerico(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '1234567890a',
            'email' => 'joao@example.com',
            'renda_mensal' => 3000.00,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['cpf']);
    }

    public function test_nao_cria_cliente_com_email_formato_invalido(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '12345678901',
            'email' => 'joao-sem-arroba.com',
            'renda_mensal' => 3000.00,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_nao_cria_cliente_com_renda_mensal_negativa(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'renda_mensal' => -100,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['renda_mensal']);
    }

    public function test_nao_cria_cliente_com_renda_mensal_igual_a_zero(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'renda_mensal' => 0,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['renda_mensal']);
    }

    public function test_nao_cria_cliente_com_renda_mensal_nao_numerica(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'renda_mensal' => 'muito rico',
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['renda_mensal']);
    }

    public function test_cria_cliente_sem_telefone(): void
    {
        $payload = [
            'nome' => 'João da Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'renda_mensal' => 3000.00,
        ];

        $response = $this->postJson('/api/clientes', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('clientes', ['cpf' => '12345678901', 'telefone' => null]);
    }

    public function test_atualizacao_permite_manter_proprio_cpf_e_email(): void
    {
        $cliente = Cliente::factory()->create(['cpf' => '12345678901', 'email' => 'joao@example.com']);

        $payload = [
            'nome' => 'Nome Atualizado',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'renda_mensal' => 5000.00,
        ];

        $response = $this->putJson("/api/clientes/{$cliente->id}", $payload);

        $response->assertOk()->assertJsonFragment(['nome' => 'Nome Atualizado']);
    }

    public function test_nao_atualiza_cliente_com_cpf_de_outro_cliente(): void
    {
        Cliente::factory()->create(['cpf' => '11111111111']);
        $cliente = Cliente::factory()->create(['cpf' => '22222222222']);

        $response = $this->putJson("/api/clientes/{$cliente->id}", ['cpf' => '11111111111']);

        $response->assertStatus(422)->assertJsonValidationErrors(['cpf']);
    }

    public function test_nao_atualiza_cliente_com_email_de_outro_cliente(): void
    {
        Cliente::factory()->create(['email' => 'existente@example.com']);
        $cliente = Cliente::factory()->create(['email' => 'proprio@example.com']);

        $response = $this->putJson("/api/clientes/{$cliente->id}", ['email' => 'existente@example.com']);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_nao_atualiza_cliente_com_email_invalido(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->putJson("/api/clientes/{$cliente->id}", ['email' => 'formato-invalido']);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_retorna_404_ao_atualizar_cliente_inexistente(): void
    {
        $response = $this->putJson('/api/clientes/999999', ['nome' => 'Qualquer Nome']);

        $response->assertNotFound();
    }

    public function test_lista_clientes_retorna_vazia_quando_nao_ha_registros(): void
    {
        $response = $this->getJson('/api/clientes');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_nao_remove_cliente_com_analise_de_credito_vinculada(): void
    {
        $cliente = Cliente::factory()->create();
        AnaliseCredito::factory()->create(['cliente_id' => $cliente->id]);

        $response = $this->deleteJson("/api/clientes/{$cliente->id}");

        $response->assertStatus(422)->assertJsonFragment([
            'message' => 'Não é possível remover um cliente que possui análise de crédito vinculada.',
        ]);

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }
}
