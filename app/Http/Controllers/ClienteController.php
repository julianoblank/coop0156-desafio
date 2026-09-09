<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    /**
     * Lista todos os clientes cadastrados.
     *
     * GET /api/clientes
     */
    public function index(): JsonResponse
    {
        return response()->json(Cliente::withCount('analises')->orderByDesc('id')->paginate(15));
    }

    /**
     * Cadastra um novo cliente.
     *
     * POST /api/clientes
     */
    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::create($request->validated());

        return response()->json($cliente, 201);
    }

    /**
     * Exibe os dados de um cliente específico.
     *
     * GET /api/clientes/{cliente}
     */
    public function show(Cliente $cliente): JsonResponse
    {
        $cliente->loadCount('analises');
        $cliente->load(['analises' => fn ($query) => $query->orderByDesc('created_at')]);

        return response()->json($cliente);
    }

    /**
     * Atualiza os dados de um cliente existente.
     *
     * PUT /api/clientes/{cliente}
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return response()->json($cliente);
    }

    /**
     * Remove um cliente do sistema.
     *
     * DELETE /api/clientes/{cliente}
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        if ($cliente->analises()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover um cliente que possui análise de crédito vinculada.',
            ], 422);
        }

        $cliente->delete();

        return response()->json(null, 204);
    }
}
