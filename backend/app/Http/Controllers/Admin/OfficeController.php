<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Office::with(['subscription.plan', 'companies'])
            ->withCount('companies')
            ->where('type', '!=', 'admin');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                    ->orWhere('cnpj', 'like', "%{$request->search}%")
                    ->orWhere('email', 'ilike', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $offices = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($offices);
    }

    public function show(Office $office): JsonResponse
    {
        $office->load([
            'subscription.plan',
            'subscriptions.plan',
            'companies',
            'users',
            'invoices' => fn ($q) => $q->latest()->limit(10),
            'parentOffice',
        ])->loadCount(['companies', 'users']);

        return response()->json($office);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cnpj' => 'required|string|max:18|min:11|unique:offices,cnpj',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            'municipio_ibge' => 'nullable|string|max:7',
            'uf' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:9',
            'ie' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $validated['type'] = 'contador';

        $office = Office::create($validated);

        if (! empty($validated['plan_id'])) {
            $plan = Plan::findOrFail($validated['plan_id']);
            $office->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
            ]);
        }

        return response()->json($office->load('subscription.plan'), 201);
    }

    public function update(Request $request, Office $office): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'cnpj' => "sometimes|string|max:18|min:11|unique:offices,cnpj,{$office->id}",
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            'municipio_ibge' => 'nullable|string|max:7',
            'uf' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:9',
            'ie' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'inactivation_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $office->update($validated);

        return response()->json($office->load('subscription.plan'));
    }

    public function destroy(Office $office): JsonResponse
    {
        if ($office->companies()->exists()) {
            return response()->json(['message' => 'Não é possível excluir um escritório com empresas vinculadas.'], 422);
        }

        $office->delete();

        return response()->json(null, 204);
    }

    public function assignPlan(Request $request, Office $office): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $office->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        $subscription = $office->subscriptions()->create([
            'plan_id' => $validated['plan_id'],
            'status' => 'active',
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return response()->json($subscription->load('plan'));
    }

    public function removePlan(Office $office): JsonResponse
    {
        $activeSubscription = $office->subscriptions()->where('status', 'active')->first();

        if (! $activeSubscription) {
            return response()->json(['message' => 'Este escritório não possui um plano ativo.'], 422);
        }

        $office->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        $office->refresh();

        return response()->json($office->load('subscription.plan'));
    }

    public function map(): JsonResponse
    {
        $contadores = Office::with(['companies' => fn ($q) => $q->select('id', 'office_id', 'razao_social', 'fantasia', 'cnpj', 'ambiente', 'is_active'), 'subscription.plan'])
            ->withCount('companies')
            ->where('type', 'contador')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $diretas = Office::with(['companies' => fn ($q) => $q->select('id', 'office_id', 'razao_social', 'fantasia', 'cnpj', 'ambiente', 'is_active'), 'subscription.plan'])
            ->withCount('companies')
            ->where('type', 'direct')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'contadores' => $contadores,
            'diretas' => $diretas,
            'totals' => [
                'contadores' => $contadores->count(),
                'direct' => $diretas->count(),
                'companies' => $contadores->sum('companies_count') + $diretas->sum('companies_count'),
            ],
        ]);
    }

    public function growthChart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'period' => 'required|in:daily,weekly,monthly',
        ]);

        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end'])->endOfDay();
        $period = $validated['period'];

        $offices = Office::where('type', '!=', 'admin')
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at']);

        $grouped = $offices->groupBy(function ($office) use ($period) {
            $date = Carbon::parse($office->created_at);

            return match ($period) {
                'daily' => $date->format('Y-m-d'),
                'weekly' => $date->copy()->startOfWeek()->format('Y-m-d'),
                'monthly' => $date->format('Y-m'),
            };
        });

        $current = $start->copy();
        $data = [];

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

            $data[] = [
                'date' => $key,
                'label' => $label,
                'count' => $grouped->get($key, collect())->count(),
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
