<?php

namespace App\Http\Controllers;

use App\Services\EstoqueService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    use HasPagination;

    public function __construct(private EstoqueService $service) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');
        $query = $this->service->getPositions($company, $request);

        return response()->json($this->paginate($query, $request));
    }

    public function ajuste(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'produto_id' => ['required', 'exists:produtos,id'],
            'tipo' => ['required', 'in:entrada,saida,ajuste'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'custo_unitario' => ['nullable', 'numeric', 'min:0'],
            'localizacao' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ]);

        $company = app('current_company');
        $movimentacao = $this->service->ajuste($company, $validated);

        return response()->json($movimentacao->load('estoque.produto'), 201);
    }

    public function movimentacoes(Request $request): JsonResponse
    {
        $company = app('current_company');
        $query = $this->service->getMovimentacoes($company, $request);

        return response()->json($this->paginate($query, $request));
    }

    public function resumo(Request $request): JsonResponse
    {
        $company = app('current_company');

        $totalProdutos = $company->produtos()->count();
        $comEstoque = $company->estoques()->where('quantidade', '>', 0)->count();
        $semEstoque = $company->estoques()->where('quantidade', '<=', 0)->count();
        $estoqueBaixo = $company->estoques()
            ->whereHas('produto', function ($q) {
                $q->whereColumn('produtos.estoque_minimo', '>', 'estoques.quantidade');
            })->count();

        $valorTotal = $company->estoques()
            ->selectRaw('SUM(quantidade * custo_medio) as total')
            ->value('total') ?? 0;

        return response()->json([
            'total_produtos' => $totalProdutos,
            'com_estoque' => $comEstoque,
            'sem_estoque' => $semEstoque,
            'estoque_baixo' => $estoqueBaixo,
            'valor_total_estoque' => (float) $valorTotal,
        ]);
    }
}
