<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckCompanyDelinquency extends Command
{
    protected $signature = 'app:check-company-delinquency {--dry-run : Simular sem aplicar mudanças}';

    protected $description = 'Verifica faturas vencidas de empresas e inativa empresas inadimplentes';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $now = Carbon::now();

        if ($dryRun) {
            $this->warn('=== MODO SIMULAÇÃO (dry-run) ===');
        }

        $invoicesMarked = 0;
        $companiesInactivated = 0;

        $companies = Company::where('is_active', true)
            ->whereHas('subscription', fn ($q) => $q->where('status', 'active'))
            ->get();

        foreach ($companies as $company) {
            $gracePeriod = 7;
            $maxOverdue = 30;

            $overdueInvoices = CompanyInvoice::where('company_id', $company->id)
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

            $totalOverdueInvoices = CompanyInvoice::where('company_id', $company->id)
                ->where('status', 'overdue')
                ->count();

            if ($totalOverdueInvoices > 0) {
                $oldestOverdue = CompanyInvoice::where('company_id', $company->id)
                    ->where('status', 'overdue')
                    ->oldest('due_at')
                    ->first();

                if ($oldestOverdue) {
                    $daysOverdue = $now->diffInDays(Carbon::parse($oldestOverdue->due_at));

                    if ($daysOverdue >= $maxOverdue) {
                        $this->warn("Empresa '{$company->razao_social}' inadimplente há {$daysOverdue} dias (limite: {$maxOverdue}).");

                        if (! $dryRun) {
                            $company->update(['is_active' => false]);
                            $company->subscription()->where('status', 'active')
                                ->update(['status' => 'expired', 'ends_at' => $now]);
                        }

                        $companiesInactivated++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Faturas de empresas marcadas como vencidas: {$invoicesMarked}");
        $this->info("Empresas inativadas: {$companiesInactivated}");

        return self::SUCCESS;
    }
}
