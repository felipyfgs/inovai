<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyInvoice;
use App\Models\OfficePlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $officeId = $request->user()->office_id;

        $query = CompanyInvoice::with(['company', 'officePlan', 'items'])
            ->where('office_id', $officeId)
            ->latest();

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reference')) {
            $query->where('reference', $request->reference);
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function show(CompanyInvoice $invoice): JsonResponse
    {
        $user = request()->user();

        if ($invoice->office_id !== $user->office_id) {
            abort(403, 'Sem permissão.');
        }

        return response()->json($invoice->load(['company', 'officePlan', 'items']));
    }

    public function update(Request $request, CompanyInvoice $invoice): JsonResponse
    {
        $user = $request->user();

        if ($invoice->office_id !== $user->office_id) {
            abort(403, 'Sem permissão.');
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,paid,cancelled,overdue',
            'amount' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
            'due_at' => 'sometimes|date',
            'paid_at' => 'nullable|date',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'paid' && empty($validated['paid_at'])) {
            $validated['paid_at'] = now();
        }

        $invoice->update($validated);

        return response()->json($invoice->load(['company', 'officePlan', 'items']));
    }

    public function dashboard(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        $officeId = $request->user()->office_id;
        $now = now();
        $start = $request->filled('start') ? Carbon::parse($request->start) : $now->copy()->startOfMonth();
        $end = $request->filled('end') ? Carbon::parse($request->end)->endOfDay() : $now;

        $periodStart = $start->copy();
        $periodLength = $periodStart->diffInDays($end);
        $previousStart = $periodStart->copy()->subDays($periodLength);
        $previousEnd = $periodStart->copy()->subDay();

        $revenue = CompanyInvoice::where('office_id', $officeId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$periodStart, $end])
            ->sum('amount');

        $previousRevenue = CompanyInvoice::where('office_id', $officeId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->sum('amount');

        $revenueVariation = $previousRevenue > 0
            ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 0;

        $pending = CompanyInvoice::where('office_id', $officeId)
            ->where('status', 'pending')
            ->sum('amount');

        $overdue = CompanyInvoice::where('office_id', $officeId)
            ->where('status', 'pending')
            ->where('due_at', '<', $now)
            ->sum('amount');

        $totalCompanies = Company::where('office_id', $officeId)
            ->where('is_active', true)
            ->count();

        $inadimplentes = Company::where('office_id', $officeId)
            ->whereHas('companyInvoices', fn ($q) => $q->where('status', 'pending')->where('due_at', '<', $now))
            ->count();

        return response()->json([
            'revenue' => $revenue,
            'previous_revenue' => $previousRevenue,
            'revenue_variation' => $revenueVariation,
            'pending' => $pending,
            'overdue' => $overdue,
            'total_companies' => $totalCompanies,
            'inadimplentes' => $inadimplentes,
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'period' => 'required|in:daily,weekly,monthly',
        ]);

        $officeId = $request->user()->office_id;
        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end'])->endOfDay();
        $period = $validated['period'];

        $invoices = CompanyInvoice::where('office_id', $officeId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->get(['paid_at', 'amount']);

        $grouped = $invoices->groupBy(function ($invoice) use ($period) {
            $date = Carbon::parse($invoice->paid_at);

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
                'amount' => $grouped->get($key, collect())->sum('amount'),
            ];

            $current = match ($period) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'monthly' => $current->addMonth(),
            };
        }

        return response()->json($data);
    }

    public function plansChart(): JsonResponse
    {
        $officeId = request()->user()->office_id;

        $plans = OfficePlan::where('office_id', $officeId)
            ->withCount(['companySubscriptions' => fn ($q) => $q->where('status', 'active')])
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $data = $plans->map(fn ($plan) => [
            'plan' => $plan->name,
            'subscribers' => $plan->company_subscriptions_count,
            'mrr' => $plan->company_subscriptions_count * $plan->price,
        ]);

        return response()->json($data);
    }

    public function generateMonthly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:10',
            'due_at' => 'required|date',
        ]);

        $officeId = $request->user()->office_id;

        $companies = Company::where('office_id', $officeId)
            ->where('is_active', true)
            ->whereHas('subscription', fn ($q) => $q->where('status', 'active'))
            ->with('subscription.officePlan')
            ->get();

        $created = 0;

        foreach ($companies as $company) {
            $alreadyExists = CompanyInvoice::where('company_id', $company->id)
                ->where('reference', $validated['reference'])
                ->exists();

            if ($alreadyExists || ! $company->subscription) {
                continue;
            }

            $plan = $company->subscription->officePlan;

            $invoice = CompanyInvoice::create([
                'company_id' => $company->id,
                'office_id' => $officeId,
                'office_plan_id' => $plan->id,
                'status' => 'pending',
                'amount' => $plan->price,
                'reference' => $validated['reference'],
                'due_at' => $validated['due_at'],
            ]);

            $invoice->items()->create([
                'description' => "Mensalidade {$plan->name} - {$validated['reference']}",
                'quantity' => 1,
                'unit_price' => $plan->price,
                'total' => $plan->price,
            ]);

            $created++;
        }

        return response()->json(['message' => "{$created} faturas geradas.", 'created' => $created]);
    }
}
