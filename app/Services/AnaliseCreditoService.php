<?php

namespace App\Services;

use App\Enums\StatusAnalise;
use App\Jobs\ProcessarContratacaoJob;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AnaliseCreditoService
{
    private const RENDA_MINIMA = 1500.0;

    private const SCORE_MINIMO = 400;

    private const SCORE_TAXA_REDUZIDA = 700;

    private const TAXA_JUROS_PADRAO = 4.5;

    private const TAXA_JUROS_REDUZIDA = 2.9;

    private const PARCELAS = 12;

    private const COMPROMETIMENTO_MAXIMO = 0.3;

    /**
     * Processa uma solicitação de análise de crédito: localiza/cadastra o cliente,
     * persiste a análise, consulta o Bureau e aplica as regras de crédito.
     */
    public function solicitar(array $dados): AnaliseCredito
    {
        $cpf = preg_replace('/\D/', '', $dados['cpf']);

        $cliente = Cliente::firstOrCreate(
            ['cpf' => $cpf],
            [
                'nome' => $dados['nome'],
                'email' => "cliente-{$cpf}@auto.coop0156.com",
                'renda_mensal' => $dados['renda_mensal'],
            ]
        );

        $analise = AnaliseCredito::create([
            'cliente_id' => $cliente->id,
            'cpf' => $cpf,
            'nome' => $dados['nome'],
            'renda_mensal' => $dados['renda_mensal'],
            'tipo_credito' => $dados['tipo_credito'],
            'valor_solicitado' => $dados['valor_solicitado'],
            'status' => StatusAnalise::PENDENTE,
        ]);

        $score = $this->consultarScore($cpf);

        if ($score === null) {
            $analise->update([
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Não foi possível consultar o Bureau de Crédito no momento.',
            ]);

            return $analise;
        }

        $this->aplicarRegrasDeCredito($analise, $score);

        return $analise;
    }

    /**
     * Transiciona uma análise aprovada para processamento e dispara o Job de contratação.
     */
    public function contratar(AnaliseCredito $analise): AnaliseCredito
    {
        $analise->update(['status' => StatusAnalise::PROCESSANDO_CONTRATACAO]);

        ProcessarContratacaoJob::dispatch($analise->id);
 
        return $analise->refresh();
    }

    /**
     * Consulta o score do Bureau. Retorna null em qualquer cenário de falha
     * (timeout, HTTP de erro ou resposta malformada sem a chave "score").
     */
    private function consultarScore(string $cpf): ?int
    {
        $baseUrl = rtrim(config('services.score_bureau.url'), '/');

        try {
            $response = Http::timeout((int) config('services.score_bureau.timeout', 3))
                ->get("{$baseUrl}/{$cpf}");
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $score = $response->json('score');

        return is_numeric($score) ? (int) $score : null;
    }

    private function aplicarRegrasDeCredito(AnaliseCredito $analise, int $score): void
    {
        $rendaMensal = (float) $analise->renda_mensal;
        $valorSolicitado = (float) $analise->valor_solicitado;

        if ($rendaMensal < self::RENDA_MINIMA) {
            $analise->update([
                'score' => $score,
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Renda mínima insuficiente',
            ]);

            return;
        }

        if ($score < self::SCORE_MINIMO) {
            $analise->update([
                'score' => $score,
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Score de crédito muito baixo',
            ]);

            return;
        }

        $taxaJuros = $score >= self::SCORE_TAXA_REDUZIDA
            ? self::TAXA_JUROS_REDUZIDA
            : self::TAXA_JUROS_PADRAO;

        $jurosTotal = $valorSolicitado * ($taxaJuros / 100) * self::PARCELAS;
        $valorTotal = $valorSolicitado + $jurosTotal;
        $valorParcela = round($valorTotal / self::PARCELAS, 2);

        if ($valorParcela > $rendaMensal * self::COMPROMETIMENTO_MAXIMO) {
            $analise->update([
                'score' => $score,
                'status' => StatusAnalise::REPROVADO,
                'taxa_juros' => $taxaJuros,
                'valor_parcela' => $valorParcela,
                'motivo_rejeicao' => 'Comprometimento de renda superior a 30%',
            ]);

            return;
        }

        $analise->update([
            'score' => $score,
            'status' => StatusAnalise::APROVADO,
            'taxa_juros' => $taxaJuros,
            'valor_parcela' => $valorParcela,
            'motivo_rejeicao' => null,
        ]);
    }
}
