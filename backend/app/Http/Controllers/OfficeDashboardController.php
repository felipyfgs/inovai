<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\NotaFiscal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OfficeDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $officeId = $user->office_id;
        $cacheKey = "dashboard:office:{$officeId}";

        $data = Cache::remember($cacheKey, 300, function () use ($officeId) {
            $companies = Company::where('office_id', $officeId)->get();
            $companyIds = $companies->pluck('id');

            $totalNfe = NotaFiscal::whereIn('company_id', $companyIds)->count();
            $nfeAutorizadas = NotaFiscal::whereIn('company_id', $companyIds)->where('status', 'autorizada')->count();
            $totalCte = Cte::whereIn('company_id', $companyIds)->count();
            $totalMdfe = Mdfe::whereIn('company_id', $companyIds)->count();

            $certificadosVencendo = $companies->filter(function ($c) {
                return $c->certificado_validade && $c->certificado_validade->diffInDays(now()) <= 30 && $c->certificado_validade->isFuture();
            })->count();

            $certificadosVencidos = $companies->filter(function ($c) {
                return $c->certificado_validade && $c->certificado_validade->isPast();
            })->count();

            return [
                'total_empresas' => $companies->count(),
                'total_nfe' => $totalNfe,
                'nfe_autorizadas' => $nfeAutorizadas,
                'total_cte' => $totalCte,
                'total_mdfe' => $totalMdfe,
                'certificados_vencendo' => $certificadosVencendo,
                'certificados_vencidos' => $certificadosVencidos,
            ];
        });

        return response()->json($data);
    }
}
