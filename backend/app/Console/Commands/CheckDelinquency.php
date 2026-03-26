<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDelinquency extends Command
{
    protected $signature = 'app:check-delinquency {--dry-run : Simular sem aplicar mudanças}';

    protected $description = 'Verifica faturas vencidas e inativa escritórios inadimplentes';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $now = Carbon::now();

        if ($dryRun) {
            $this->warn('=== MODO SIMULAÇÃO (dry-run) ===');
        }

        $invoicesMarked = 0;
        $officesInactivated = 0;

        $offices = Office::with('subscription.plan')
            ->where('is_active', true)
            ->where('type', '!=', 'admin')
            ->whereHas('subscription', fn ($q) => $q->where('status', 'active'))
            ->get();

        foreach ($offices as $office) {
            $plan = $office->subscription?->plan;

            if (! $plan) {
                continue;
            }

            $gracePeriod = $plan->grace_period_days ?? 7;
            $maxOverdue = $plan->max_overdue_days ?? 30;

            $overdueInvoices = Invoice::where('office_id', $office->id)
                ->where('status', 'pending')
                ->where('due_at', '<', $now)
                ->get();

            foreach ($overdueInvoices as $invoice) {
                $daysSinceDue = $now->diffInDays(Carbon::parse($invoice->due_at));

                if ($daysSinceDue >= $gracePeriod && $invoice->status === 'pending') {
                    if (! $dryRun) {
                        $invoice->update(['status' => 'overdue']);
                    }
                    $invoicesMarked++;
                }
            }

            $totalOverdueInvoices = Invoice::where('office_id', $office->id)
                ->where('status', 'overdue')
                ->count();

            if ($totalOverdueInvoices > 0) {
                $oldestOverdue = Invoice::where('office_id', $office->id)
                    ->where('status', 'overdue')
                    ->oldest('due_at')
                    ->first();

                if ($oldestOverdue) {
                    $daysOverdue = $now->diffInDays(Carbon::parse($oldestOverdue->due_at));

                    if ($daysOverdue >= $maxOverdue) {
                        $this->warn("Escritório '{$office->name}' inadimplente há {$daysOverdue} dias (limite: {$maxOverdue}).");

                        if (! $dryRun) {
                            $office->update([
                                'is_active' => false,
                                'inactivated_at' => $now,
                                'inactivation_reason' => "Inadimplência: {$totalOverdueInvoices} fatura(s) vencida(s) há mais de {$maxOverdue} dias.",
                            ]);
                        }

                        $officesInactivated++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Faturas marcadas como vencidas: {$invoicesMarked}");
        $this->info("Escritórios inativados: {$officesInactivated}");

        return self::SUCCESS;
    }
}
