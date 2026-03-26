<?php

namespace App\Services;

use App\Models\Company;
use App\Models\NotaFiscal;
use App\Models\Office;
use Carbon\Carbon;

class PlanLimitService
{
    /**
     * Check if company can emit a new NF based on plan limits
     */
    public function canEmitNf(Company $company): array
    {
        $office = $company->office;

        if (! $office) {
            return [
                'allowed' => false,
                'reason' => 'Empresa sem escritório vinculado.',
            ];
        }

        $subscription = $office->subscription;

        if (! $subscription || ! $subscription->plan) {
            return [
                'allowed' => false,
                'reason' => 'Nenhuma assinatura ativa encontrada.',
            ];
        }

        $plan = $subscription->plan;
        $maxNfs = $plan->max_nfs_month;

        if ($maxNfs === null || $maxNfs === 0) {
            return [
                'allowed' => true,
                'remaining' => null,
                'limit' => null,
            ];
        }

        $currentMonthCount = $this->getNfCountCurrentMonth($company);

        if ($currentMonthCount >= $maxNfs) {
            return [
                'allowed' => false,
                'reason' => "Limite de {$maxNfs} nota(s) fiscal(is) mensal atingido.",
                'current' => $currentMonthCount,
                'limit' => $maxNfs,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $maxNfs - $currentMonthCount,
            'current' => $currentMonthCount,
            'limit' => $maxNfs,
        ];
    }

    /**
     * Get NF count for current month
     */
    public function getNfCountCurrentMonth(Company $company): int
    {
        return NotaFiscal::where('company_id', $company->id)
            ->whereYear('data_emissao', Carbon::now()->year)
            ->whereMonth('data_emissao', Carbon::now()->month)
            ->whereIn('status', ['autorizada', 'emitindo', 'pendente'])
            ->count();
    }

    /**
     * Check company limit for an office
     */
    public function canCreateCompany(Office $office): array
    {
        $subscription = $office->subscription;

        if (! $subscription || ! $subscription->plan) {
            return [
                'allowed' => false,
                'reason' => 'Nenhuma assinatura ativa encontrada.',
            ];
        }

        $plan = $subscription->plan;
        $maxCompanies = $plan->max_companies;

        if ($maxCompanies === null) {
            return [
                'allowed' => true,
                'remaining' => null,
                'limit' => null,
            ];
        }

        $currentCount = $office->companies()->count();

        if ($currentCount >= $maxCompanies) {
            return [
                'allowed' => false,
                'reason' => "Limite de {$maxCompanies} empresa(s) atingido.",
                'current' => $currentCount,
                'limit' => $maxCompanies,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $maxCompanies - $currentCount,
            'current' => $currentCount,
            'limit' => $maxCompanies,
        ];
    }

    /**
     * Get usage stats for a company
     */
    public function getUsageStats(Company $company): array
    {
        $office = $company->office;

        if (! $office?->subscription?->plan) {
            return [];
        }

        $plan = $office->subscription->plan;

        return [
            'companies' => [
                'current' => $office->companies()->count(),
                'limit' => $plan->max_companies,
            ],
            'nfs_month' => [
                'current' => $this->getNfCountCurrentMonth($company),
                'limit' => $plan->max_nfs_month,
            ],
        ];
    }
}
