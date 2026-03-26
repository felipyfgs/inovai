<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrcamentoRequest;
use App\Http\Requests\UpdateOrcamentoRequest;
use App\Http\Resources\OrcamentoResource;
use App\Models\Orcamento;
use App\Services\OrcamentoService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrcamentoController extends Controller
{
    use HasPagination;

    public function __construct(
        private OrcamentoService $orcamentoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->orcamentos()->with('pessoa');

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

    public function store(StoreOrcamentoRequest $request): JsonResponse
    {
        $company = app('current_company');
        $orcamento = $this->orcamentoService->createOrcamento($request->validated(), $company);

        return response()->json(OrcamentoResource::make($orcamento->load(['pessoa', 'itens.produto'])), 201);
    }

    public function show(Orcamento $orcamento): JsonResponse
    {
        $this->authorizeResource($orcamento);

        return response()->json(OrcamentoResource::make($orcamento->load(['pessoa', 'itens.produto'])));
    }

    public function update(UpdateOrcamentoRequest $request, Orcamento $orcamento): JsonResponse
    {
        $this->authorizeResource($orcamento);

        if ($orcamento->status === 'convertido') {
            return response()->json(['message' => 'Orçamento já convertido em pedido.'], 422);
        }

        $orcamento = $this->orcamentoService->updateOrcamento($orcamento, $request->validated());

        return response()->json(OrcamentoResource::make($orcamento->load(['pessoa', 'itens.produto'])));
    }

    public function destroy(Orcamento $orcamento): JsonResponse
    {
        $this->authorizeResource($orcamento);
        $orcamento->delete();

        return response()->json(['message' => 'Orçamento removido com sucesso.']);
    }

    public function convertToPedido(Orcamento $orcamento): JsonResponse
    {
        $this->authorizeResource($orcamento);

        if ($orcamento->status === 'convertido') {
            return response()->json(['message' => 'Orçamento já foi convertido.'], 422);
        }

        $company = app('current_company');
        $pedido = $this->orcamentoService->convertToPedido($orcamento, $company);

        return response()->json($pedido->load(['pessoa', 'itens.produto']), 201);
    }

    private function authorizeResource(Orcamento $orcamento): void
    {
        $company = app('current_company');
        if ($orcamento->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}
