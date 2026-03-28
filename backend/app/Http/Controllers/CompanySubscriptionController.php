<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\OfficePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $officeId = $request->user()->office_id;

        $companies = Company::where('office_id', $officeId)
            ->with(['subscription.officePlan', 'officePlan'])
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->whereHas('subscription', fn ($sq) => $sq->where('status', 'active'));
                } elseif ($request->status === 'none') {
                    $q->whereDoesntHave('subscription', fn ($sq) => $sq->where('status', 'active'));
                } else {
                    $q->whereHas('subscription', fn ($sq) => $sq->where('status', $request->status));
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($sq) use ($request) {
                    $sq->where('razao_social', 'ilike', "%{$request->search}%")
                        ->orWhere('fantasia', 'ilike', "%{$request->search}%")
                        ->orWhere('cnpj', 'ilike', "%{$request->search}%");
                });
            })
            ->orderBy('razao_social')
            ->paginate($request->get('per_page', 20));

        return response()->json($companies);
    }

    public function assignPlan(Request $request, Company $company): JsonResponse
    {
        $user = $request->user();

        if ($company->office_id !== $user->office_id) {
            abort(403, 'Sem permissão.');
        }

        $validated = $request->validate([
            'office_plan_id' => 'required|exists:office_plans,id',
        ]);

        $officePlan = OfficePlan::where('id', $validated['office_plan_id'])
            ->where('office_id', $user->office_id)
            ->firstOrFail();

        CompanySubscription::where('company_id', $company->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        CompanySubscription::create([
            'company_id' => $company->id,
            'office_plan_id' => $officePlan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $company->update(['office_plan_id' => $officePlan->id]);

        return response()->json($company->load(['subscription.officePlan', 'officePlan']));
    }

    public function cancel(Request $request, Company $company): JsonResponse
    {
        $user = $request->user();

        if ($company->office_id !== $user->office_id) {
            abort(403, 'Sem permissão.');
        }

        CompanySubscription::where('company_id', $company->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'ends_at' => now()]);

        $company->update(['office_plan_id' => null]);

        return response()->json(['message' => 'Assinatura cancelada.']);
    }
}
