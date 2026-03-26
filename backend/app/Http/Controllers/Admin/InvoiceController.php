<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['office', 'plan', 'items'])
            ->latest();

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reference')) {
            $query->where('reference', $request->reference);
        }

        $invoices = $query->paginate($request->get('per_page', 15));

        return response()->json($invoices);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load(['office', 'plan', 'items']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'in:pending,paid,cancelled,overdue',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'due_at' => 'required|date',
            'paid_at' => 'nullable|date',
            'items' => 'nullable|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $invoice = Invoice::create($validated);

        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
        }

        return response()->json($invoice->load(['office', 'plan', 'items']), 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
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

        return response()->json($invoice->load(['office', 'plan', 'items']));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(null, 204);
    }

    public function dashboard(): JsonResponse
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $mrr = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startOfMonth, $now])
            ->sum('amount');

        $lastMonthRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $pending = Invoice::where('status', 'pending')->sum('amount');
        $overdue = Invoice::where('status', 'pending')->where('due_at', '<', $now)->sum('amount');

        $totalOffices = Office::where('type', '!=', 'admin')->where('is_active', true)->count();
        $inadimplentes = Office::where('type', '!=', 'admin')
            ->whereHas('invoices', fn ($q) => $q->where('status', 'pending')->where('due_at', '<', $now))
            ->count();

        return response()->json([
            'mrr' => $mrr,
            'last_month_revenue' => $lastMonthRevenue,
            'pending' => $pending,
            'overdue' => $overdue,
            'total_offices' => $totalOffices,
            'inadimplentes' => $inadimplentes,
            'churn_rate' => $totalOffices > 0 ? round(($inadimplentes / $totalOffices) * 100, 2) : 0,
        ]);
    }

    public function generateMonthly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:10',
            'due_at' => 'required|date',
        ]);

        $offices = Office::with('subscription.plan')
            ->where('type', '!=', 'admin')
            ->where('is_active', true)
            ->whereHas('subscription', fn ($q) => $q->where('status', 'active'))
            ->get();

        $created = 0;
        foreach ($offices as $office) {
            $alreadyExists = Invoice::where('office_id', $office->id)
                ->where('reference', $validated['reference'])
                ->exists();

            if ($alreadyExists || ! $office->subscription) {
                continue;
            }

            $plan = $office->subscription->plan;
            $invoice = Invoice::create([
                'office_id' => $office->id,
                'plan_id' => $plan->id,
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
