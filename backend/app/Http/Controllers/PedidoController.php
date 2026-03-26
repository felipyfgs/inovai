<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedidoRequest;
use App\Http\Requests\UpdatePedidoRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Services\PedidoService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    use HasPagination;

    public function __construct(
        private PedidoService $pedidoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->pedidos()->with('pessoa');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhereHas('pessoa', fn ($p) => $p->where('razao_social', 'ilike', "%{$search}%"));
            });
        }

        return response()->json($this->paginate($query, $request));
    }

    public function store(StorePedidoRequest $request): JsonResponse
    {
        $company = app('current_company');
        $pedido = $this->pedidoService->createPedido($request->validated(), $company);

        return response()->json(PedidoResource::make($pedido->load(['pessoa', 'itens.produto'])), 201);
    }

    public function show(Pedido $pedido): JsonResponse
    {
        $this->authorizeResource($pedido);

        return response()->json(PedidoResource::make($pedido->load(['pessoa', 'itens.produto', 'orcamento'])));
    }

    public function update(UpdatePedidoRequest $request, Pedido $pedido): JsonResponse
    {
        $this->authorizeResource($pedido);

        if ($pedido->status === 'faturado') {
            return response()->json(['message' => 'Pedido já faturado, não pode ser alterado.'], 422);
        }

        $pedido = $this->pedidoService->updatePedido($pedido, $request->validated());

        return response()->json(PedidoResource::make($pedido->load(['pessoa', 'itens.produto'])));
    }

    public function destroy(Pedido $pedido): JsonResponse
    {
        $this->authorizeResource($pedido);

        if ($pedido->status === 'faturado') {
            return response()->json(['message' => 'Pedido faturado não pode ser removido.'], 422);
        }

        $pedido->delete();

        return response()->json(['message' => 'Pedido removido com sucesso.']);
    }

    private function authorizeResource(Pedido $pedido): void
    {
        $company = app('current_company');
        if ($pedido->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}
