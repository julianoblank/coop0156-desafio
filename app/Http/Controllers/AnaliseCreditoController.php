<?php

namespace App\Http\Controllers;

use App\Enums\StatusAnalise;
use App\Http\Requests\StoreAnaliseCreditoRequest;
use App\Models\AnaliseCredito;
use App\Services\AnaliseCreditoService;
use Illuminate\Http\JsonResponse;

class AnaliseCreditoController extends Controller
{
    public function __construct(private readonly AnaliseCreditoService $service)
    {
        //
    }

    /**
     * Solicita uma nova análise de crédito.
     *
     * POST /api/analise-credito
     */
    public function solicitar(StoreAnaliseCreditoRequest $request): JsonResponse
    {
        $analise = $this->service->solicitar($request->validated());

        return response()->json($analise, 201);
    }

    /**
     * Confirma a contratação de uma análise de crédito aprovada.
     *
     * POST /api/analise-credito/{id}/contratar
     */
    public function contratar(int $id): JsonResponse
    {
        $analise = AnaliseCredito::findOrFail($id);

        if ($analise->status !== StatusAnalise::APROVADO) {
            return response()->json([
                'message' => 'A análise de crédito não está aprovada para contratação.',
            ], 422);
        }

        $analise = $this->service->contratar($analise);

        return response()->json($analise);
    }
}
