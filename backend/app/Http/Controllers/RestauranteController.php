<?php

namespace App\Http\Controllers;

use App\Models\RestauranteCardapioItem;
use App\Models\RestauranteComanda;
use App\Models\RestauranteMesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestauranteController extends Controller
{
    public function mesas(Request $request): JsonResponse
    {
        $company = app('current_company');
        $mesas = $company->restauranteMesas()->orderBy('nome')->get();

        return response()->json($mesas);
    }

    public function createMesa(Request $request): JsonResponse
    {
        $company = app('current_company');
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:50'],
            'capacidade' => ['nullable', 'integer', 'min:1'],
            'localizacao' => ['nullable', 'string'],
        ]);
        $validated['company_id'] = $company->id;
        $mesa = RestauranteMesa::create($validated);

        return response()->json($mesa, 201);
    }

    public function cardapio(Request $request): JsonResponse
    {
        $company = app('current_company');
        $grupos = $company->restauranteCardapioGrupos()
            ->with('itens')
            ->orderBy('ordem')
            ->get();

        return response()->json($grupos);
    }

    public function createItem(Request $request): JsonResponse
    {
        $company = app('current_company');
        $validated = $request->validate([
            'grupo_id' => ['required', 'exists:restaurante_cardapio_grupos,id'],
            'nome' => ['required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string'],
            'preco' => ['required', 'numeric', 'min:0.01'],
            'codigo' => ['nullable', 'string', 'max:20'],
        ]);
        $validated['company_id'] = $company->id;
        $item = RestauranteCardapioItem::create($validated);

        return response()->json($item, 201);
    }

    public function comandas(Request $request): JsonResponse
    {
        $company = app('current_company');
        $comandas = $company->restauranteComandas()
            ->with(['mesa', 'itens', 'garcom'])
            ->orderByDesc('opened_at')
            ->get();

        return response()->json($comandas);
    }

    public function abrirComanda(Request $request): JsonResponse
    {
        $company = app('current_company');
        $validated = $request->validate([
            'mesa_id' => ['nullable', 'exists:restaurante_mesas,id'],
            'pessoas' => ['nullable', 'integer', 'min:1'],
        ]);
        $validated['company_id'] = $company->id;
        $validated['codigo'] = RestauranteComanda::gerarCodigo();
        $validated['status'] = 'aberta';
        $validated['opened_at'] = now();
        $validated['garcom_id'] = $request->user()->id;

        $comanda = RestauranteComanda::create($validated);

        if (! empty($validated['mesa_id'])) {
            RestauranteMesa::where('id', $validated['mesa_id'])->update(['status' => 'ocupada']);
        }

        return response()->json($comanda->load(['mesa', 'itens']), 201);
    }

    public function addItem(Request $request, RestauranteComanda $comanda): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:restaurante_cardapio_itens,id'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $item = RestauranteCardapioItem::findOrFail($validated['item_id']);

        $comandaItem = $comanda->itens()->create([
            'item_id' => $validated['item_id'],
            'quantidade' => $validated['quantidade'],
            'preco_unitario' => $item->preco,
            'valor_total' => $item->preco * $validated['quantidade'],
            'observacoes' => $validated['observacoes'] ?? null,
            'status' => 'pendente',
            'created_at' => now(),
        ]);

        $comanda->recalcularTotal();

        return response()->json($comandaItem->load('item'));
    }

    public function fecharComanda(Request $request, RestauranteComanda $comanda): JsonResponse
    {
        $validated = $request->validate([
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'pagamentos' => ['required', 'array', 'min:1'],
            'pagamentos.*.forma_pagamento' => ['required', 'string'],
            'pagamentos.*.valor' => ['required', 'numeric', 'min:0.01'],
        ]);

        $comanda->update([
            'status' => 'fechada',
            'desconto' => $validated['desconto'] ?? 0,
            'closed_at' => now(),
        ]);

        foreach ($validated['pagamentos'] as $pagamento) {
            $comanda->pagamentos()->create([
                'forma_pagamento' => $pagamento['forma_pagamento'],
                'valor' => $pagamento['valor'],
            ]);
        }

        if ($comanda->mesa) {
            $comanda->mesa->update(['status' => 'livre']);
        }

        return response()->json($comanda->load(['mesa', 'itens', 'pagamentos']));
    }

    public function resumido(): JsonResponse
    {
        $company = app('current_company');

        $mesasLivres = $company->restauranteMesas()->where('status', 'livre')->count();
        $mesasOcupadas = $company->restauranteMesas()->where('status', 'ocupada')->count();
        $comandasAbertas = $company->restauranteComandas()->where('status', 'aberta')->count();
        $receitaHoje = $company->restauranteComandas()
            ->whereDate('closed_at', today())
            ->where('status', 'fechada')
            ->sum('valor_total');

        return response()->json([
            'mesas_livres' => $mesasLivres,
            'mesas_ocupadas' => $mesasOcupadas,
            'comandas_abertas' => $comandasAbertas,
            'receita_hoje' => (float) $receitaHoje,
        ]);
    }
}
