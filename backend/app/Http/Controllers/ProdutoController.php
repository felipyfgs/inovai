<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->produtos();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('descricao', 'ilike', "%{$search}%")
                  ->orWhere('codigo', 'ilike', "%{$search}%")
                  ->orWhere('codigo_barras', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        return response()->json($this->paginate($query, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:60'],
            'codigo_barras' => ['nullable', 'string', 'max:14'],
            'descricao' => ['required', 'string', 'max:255'],
            'ncm' => ['nullable', 'string', 'max:8'],
            'cest' => ['nullable', 'string', 'max:7'],
            'cfop' => ['nullable', 'string', 'max:4'],
            'unidade' => ['nullable', 'string', 'max:6'],
            'preco_venda' => ['nullable', 'numeric', 'min:0'],
            'preco_custo' => ['nullable', 'numeric', 'min:0'],
            'origem' => ['nullable', 'integer', 'between:0,8'],
            'cst_icms' => ['nullable', 'string', 'max:3'],
            'csosn' => ['nullable', 'string', 'max:4'],
            'aliq_icms' => ['nullable', 'numeric', 'min:0'],
            'aliq_ipi' => ['nullable', 'numeric', 'min:0'],
            'cst_pis' => ['nullable', 'string', 'max:2'],
            'aliq_pis' => ['nullable', 'numeric', 'min:0'],
            'cst_cofins' => ['nullable', 'string', 'max:2'],
            'aliq_cofins' => ['nullable', 'numeric', 'min:0'],
            'peso_liquido' => ['nullable', 'numeric', 'min:0'],
            'peso_bruto' => ['nullable', 'numeric', 'min:0'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = $company->id;

        $produto = Produto::create($validated);

        return response()->json($produto, 201);
    }

    public function show(Produto $produto): JsonResponse
    {
        $this->authorizeProduto($produto);

        return response()->json($produto->load('estoque'));
    }

    public function update(Request $request, Produto $produto): JsonResponse
    {
        $this->authorizeProduto($produto);

        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:60'],
            'codigo_barras' => ['nullable', 'string', 'max:14'],
            'descricao' => ['sometimes', 'string', 'max:255'],
            'ncm' => ['nullable', 'string', 'max:8'],
            'cest' => ['nullable', 'string', 'max:7'],
            'cfop' => ['nullable', 'string', 'max:4'],
            'unidade' => ['nullable', 'string', 'max:6'],
            'preco_venda' => ['nullable', 'numeric', 'min:0'],
            'preco_custo' => ['nullable', 'numeric', 'min:0'],
            'origem' => ['nullable', 'integer', 'between:0,8'],
            'cst_icms' => ['nullable', 'string', 'max:3'],
            'csosn' => ['nullable', 'string', 'max:4'],
            'aliq_icms' => ['nullable', 'numeric', 'min:0'],
            'aliq_ipi' => ['nullable', 'numeric', 'min:0'],
            'cst_pis' => ['nullable', 'string', 'max:2'],
            'aliq_pis' => ['nullable', 'numeric', 'min:0'],
            'cst_cofins' => ['nullable', 'string', 'max:2'],
            'aliq_cofins' => ['nullable', 'numeric', 'min:0'],
            'peso_liquido' => ['nullable', 'numeric', 'min:0'],
            'peso_bruto' => ['nullable', 'numeric', 'min:0'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $produto->update($validated);

        return response()->json($produto);
    }

    public function destroy(Produto $produto): JsonResponse
    {
        $this->authorizeProduto($produto);
        $produto->delete();

        return response()->json(['message' => 'Produto removido com sucesso.']);
    }

    private function authorizeProduto(Produto $produto): void
    {
        $company = app('current_company');
        if ($produto->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}
