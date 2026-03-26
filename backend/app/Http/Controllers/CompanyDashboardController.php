<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use App\Models\NotaFiscal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CompanyDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');
        $cacheKey = "dashboard:company:{$company->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($company) {
            $nfeMes = NotaFiscal::where('company_id', $company->id)
                ->whereMonth('data_emissao', now()->month)
                ->whereYear('data_emissao', now()->year);

            $faturamentoMes = (clone $nfeMes)->where('status', 'autorizada')->sum('valor_total');
            $totalImpostosMes = (clone $nfeMes)->where('status', 'autorizada')
                ->selectRaw('COALESCE(SUM(valor_icms),0) + COALESCE(SUM(valor_ipi),0) + COALESCE(SUM(valor_pis),0) + COALESCE(SUM(valor_cofins),0) as total')
                ->value('total') ?? 0;

            $produtosEstoqueBaixo = Estoque::where('estoques.company_id', $company->id)
                ->join('produtos', 'produtos.id', '=', 'estoques.produto_id')
                ->where('produtos.is_active', true)
                ->where('produtos.estoque_minimo', '>', 0)
                ->whereColumn('estoques.quantidade', '<=', 'produtos.estoque_minimo')
                ->count();

            $orcamentosPendentes = $company->orcamentos()->whereIn('status', ['rascunho', 'enviado'])->count();
            $pedidosPendentes = $company->pedidos()->whereIn('status', ['pendente', 'confirmado'])->count();

            return [
                'faturamento_mes' => round($faturamentoMes, 2),
                'impostos_mes' => round($totalImpostosMes, 2),
                'nfe_mes' => $nfeMes->count(),
                'orcamentos_pendentes' => $orcamentosPendentes,
                'pedidos_pendentes' => $pedidosPendentes,
                'produtos_estoque_baixo' => $produtosEstoqueBaixo,
                'certificado_validade' => $company->certificado_validade?->toDateString(),
            ];
        });

        return response()->json($data);
    }
}
