<?php

namespace App\Http\Controllers;

use App\Models\OfficePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficePlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $officeId = $request->header('X-Office-Id') ?? $user->office_id;

        $plans = OfficePlan::where('office_id', $officeId)
            ->active()
            ->orderBy('price')
            ->get();

        return response()->json($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_nfs_month' => ['nullable', 'integer', 'min:1'],
            'modules' => ['required', 'array'],
            'modules.*' => ['string'],
            'is_default' => ['boolean'],
        ]);

        $user = $request->user();
        $validated['office_id'] = $request->header('X-Office-Id') ?? $user->office_id;

        if ($validated['is_default'] ?? false) {
            OfficePlan::where('office_id', $validated['office_id'])
                ->update(['is_default' => false]);
        }

        $plan = OfficePlan::create($validated);

        return response()->json($plan, 201);
    }

    public function update(Request $request, OfficePlan $plan): JsonResponse
    {
        $this->authorizePlan($request, $plan);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'max_nfs_month' => ['nullable', 'integer', 'min:1'],
            'modules' => ['sometimes', 'array'],
            'modules.*' => ['string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        if (isset($validated['is_default']) && $validated['is_default']) {
            OfficePlan::where('office_id', $plan->office_id)
                ->where('id', '!=', $plan->id)
                ->update(['is_default' => false]);
        }

        $plan->update($validated);

        return response()->json($plan);
    }

    public function destroy(Request $request, OfficePlan $plan): JsonResponse
    {
        $this->authorizePlan($request, $plan);
        $plan->delete();

        return response()->json(['message' => 'Plano removido com sucesso.']);
    }

    private function authorizePlan(Request $request, OfficePlan $plan): void
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $officeId = $request->header('X-Office-Id');
            if ($officeId && $plan->office_id != $officeId) {
                abort(403, 'Sem permissão.');
            }

            return;
        }

        if ($plan->office_id !== $user->office_id) {
            abort(403, 'Sem permissão.');
        }
    }
}
