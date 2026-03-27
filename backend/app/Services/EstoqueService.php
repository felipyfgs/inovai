<?php

namespace App\Services;

use App\Models\Estoque;
use App\Models\EstoqueMovimentacao;
use App\Models\Produto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EstoqueService
{
    public function getPositions($company, $request)
    {
        $query = $company->estoques()->with('produto');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('produto', function ($q) use ($search) {
                $q->where('descricao', 'ilike', "%{$search}%")
                    ->orWhere('codigo', 'ilike', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%");
            });
        }

        if ($request->filled('localizacao')) {
            $query->where('localizacao', 'ilike', "%{$request->input('localizacao')}%");
        }

        if ($request->boolean('estoque_baixo', false)) {
            $query->whereHas('produto', function ($q) {
                $q->whereColumn('produtos.estoque_minimo', '>', 'estoques.quantidade');
            });
        }

        if ($request->boolean('sem_estoque', false)) {
            $query->where('quantidade', '<=', 0);
        }

        return $query;
    }

    public function createOrUpdateForProduto($company, $produtoId)
    {
        return Estoque::firstOrCreate(
            [
                'company_id' => $company->id,
                'produto_id' => $produtoId,
            ],
            [
                'quantidade' => 0,
                'custo_medio' => 0,
            ]
        );
    }

    public function ajuste($company, array $data): EstoqueMovimentacao
    {
        return DB::transaction(function () use ($company, $data) {
            $produto = Produto::where('company_id', $company->id)
                ->findOrFail($data['produto_id']);

            $estoque = Estoque::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'produto_id' => $produto->id,
                ],
                [
                    'quantidade' => 0,
                    'custo_medio' => 0,
                ]
            );

            $tipo = $data['tipo'];
            $quantidade = (float) $data['quantidade'];
            $custoUnitario = (float) ($data['custo_unitario'] ?? 0);

            if ($tipo === 'entrada') {
                $estoque->quantidade += $quantidade;
                if ($quantidade > 0 && $custoUnitario > 0) {
                    $totalAnterior = (float) $estoque->custo_medio * ((float) $estoque->quantidade - $quantidade);
                    $totalNovo = $custoUnitario * $quantidade;
                    $novaQtd = (float) $estoque->quantidade;
                    $estoque->custo_medio = $novaQtd > 0 ? ($totalAnterior + $totalNovo) / $novaQtd : 0;
                }
            } elseif ($tipo === 'saida') {
                if ($quantidade > (float) $estoque->quantidade) {
                    abort(422, 'Quantidade insuficiente em estoque. Disponível: '.$estoque->quantidade);
                }
                $estoque->quantidade -= $quantidade;
            } else {
                $estoque->quantidade = $quantidade;
                if ($custoUnitario > 0) {
                    $estoque->custo_medio = $custoUnitario;
                }
            }

            $estoque->localizacao = $data['localizacao'] ?? $estoque->localizacao;
            $estoque->save();

            return EstoqueMovimentacao::create([
                'estoque_id' => $estoque->id,
                'user_id' => Auth::id(),
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'custo_unitario' => $custoUnitario,
                'documento_tipo' => 'ajuste_manual',
                'documento_id' => null,
                'observacoes' => $data['observacoes'] ?? null,
                'data' => now(),
            ]);
        });
    }

    public function getMovimentacoes($company, $request)
    {
        $query = EstoqueMovimentacao::whereHas('estoque', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->with(['estoque.produto', 'user']);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('produto_id')) {
            $query->whereHas('estoque', function ($q) use ($request) {
                $q->where('produto_id', $request->input('produto_id'));
            });
        }

        if ($request->filled('data_inicio')) {
            $query->where('data', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->where('data', '<=', $request->input('data_fim').' 23:59:59');
        }

        $query->orderBy('data', 'desc');

        return $query;
    }
}
