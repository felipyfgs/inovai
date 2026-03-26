<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Plan::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                    ->orWhere('description', 'ilike', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $plans = $query->orderBy('price')->get();

        return response()->json($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'max_companies' => 'required|integer|min:1',
            'max_nfs_month' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:90',
            'max_overdue_days' => 'nullable|integer|min:0|max:365',
        ]);

        $plan = Plan::create($validated);

        return response()->json($plan, 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'max_companies' => 'sometimes|integer|min:1',
            'max_nfs_month' => 'sometimes|integer|min:0',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:90',
            'max_overdue_days' => 'nullable|integer|min:0|max:365',
        ]);

        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->subscriptions()->exists()) {
            return response()->json(['message' => 'Não é possível excluir um plano com assinaturas ativas.'], 422);
        }

        $plan->delete();

        return response()->json(null, 204);
    }
}
