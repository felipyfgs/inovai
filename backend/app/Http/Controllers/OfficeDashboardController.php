<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\Nfe;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $officeId = $user->office_id;

        $start = $request->filled('start')
            ? Carbon::parse($request->start)
            : Carbon::now()->startOfMonth();
        $end = $request->filled('end')
            ? Carbon::parse($request->end)->endOfDay()
            : Carbon::now()->endOfDay();

        $companyIds = Company::where('office_id', $officeId)->pluck('id');

        $totalNfe = Nfe::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $nfeAutorizadas = Nfe::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'autorizada')
            ->count();
        $totalCte = Cte::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $totalMdfe = Mdfe::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $companies = Company::where('office_id', $officeId)->get();
        $certificadosVencendo = $companies->filter(function ($c) {
            return $c->certificado_validade && $c->certificado_validade->diffInDays(now()) <= 30 && $c->certificado_validade->isFuture();
        })->count();

        $certificadosVencidos = $companies->filter(function ($c) {
            return $c->certificado_validade && $c->certificado_validade->isPast();
        })->count();

        return response()->json([
            'total_empresas' => $companies->count(),
            'total_nfe' => $totalNfe,
            'nfe_autorizadas' => $nfeAutorizadas,
            'total_cte' => $totalCte,
            'total_mdfe' => $totalMdfe,
            'certificados_vencendo' => $certificadosVencendo,
            'certificados_vencidos' => $certificadosVencidos,
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'period' => 'required|in:daily,weekly,monthly',
        ]);

        $user = $request->user();
        $officeId = $user->office_id;
        $companyIds = Company::where('office_id', $officeId)->pluck('id');

        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end'])->endOfDay();
        $period = $validated['period'];

        $nfeQuery = Nfe::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$start, $end]);

        $cteQuery = Cte::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$start, $end]);

        $mdfeQuery = Mdfe::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$start, $end]);

        $nfeData = (clone $nfeQuery)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as day, COUNT(*) as total")
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM-DD')")
            ->pluck('total', 'day');

        $cteData = (clone $cteQuery)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as day, COUNT(*) as total")
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM-DD')")
            ->pluck('total', 'day');

        $mdfeData = (clone $mdfeQuery)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as day, COUNT(*) as total")
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM-DD')")
            ->pluck('total', 'day');

        $data = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $key = match ($period) {
                'daily' => $current->format('Y-m-d'),
                'weekly' => $current->copy()->startOfWeek()->format('Y-m-d'),
                'monthly' => $current->format('Y-m'),
            };

            $label = match ($period) {
                'daily' => $current->format('d/m'),
                'weekly' => $current->copy()->startOfWeek()->format('d/m'),
                'monthly' => $current->copy()->startOfMonth()->format('M/Y'),
            };

            $nfeSum = 0;
            $cteSum = 0;
            $mdfeSum = 0;

            $dayEnd = match ($period) {
                'daily' => $current->copy()->endOfDay(),
                'weekly' => $current->copy()->endOfWeek(),
                'monthly' => $current->copy()->endOfMonth(),
            };

            $dayCurrent = $current->copy();
            while ($dayCurrent->lte($dayEnd) && $dayCurrent->lte($end)) {
                $dayKey = $dayCurrent->format('Y-m-d');
                $nfeSum += (int) ($nfeData[$dayKey] ?? 0);
                $cteSum += (int) ($cteData[$dayKey] ?? 0);
                $mdfeSum += (int) ($mdfeData[$dayKey] ?? 0);
                $dayCurrent->addDay();
            }

            $data[] = [
                'date' => $key,
                'label' => $label,
                'nfe' => $nfeSum,
                'cte' => $cteSum,
                'mdfe' => $mdfeSum,
            ];

            $current = match ($period) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'monthly' => $current->addMonth(),
            };
        }

        return response()->json($data);
    }
}
