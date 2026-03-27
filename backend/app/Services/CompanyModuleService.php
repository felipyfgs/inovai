<?php

namespace App\Services;

use App\Models\Company;

class CompanyModuleService
{
    public function __construct(
        private PlanLimitService $planLimitService
    ) {}

    public function syncModules(Company $company, array $modules): array
    {
        $planFeatures = $this->getPlanFeatures($company);
        $allModules = Company::availableModules();
        $validModules = array_intersect($modules, array_keys($allModules));

        $company->modules()->delete();

        foreach ($validModules as $module) {
            $company->modules()->create([
                'module' => $module,
                'is_active' => true,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Módulos atualizados com sucesso.',
            'modules' => $company->fresh()->getActiveModuleList(),
        ];
    }

    public function getAvailableModules(Company $company): array
    {
        $planFeatures = $this->getPlanFeatures($company);
        $activeModules = $company->getActiveModuleList();
        $allModules = Company::availableModules();
        $hasConfiguredModules = $company->modules()->exists();

        $available = [];
        foreach ($allModules as $key => $label) {
            $inPlan = in_array($key, $planFeatures);
            $isActive = $hasConfiguredModules
                ? in_array($key, $activeModules)
                : $inPlan;
            $available[] = [
                'id' => $key,
                'label' => $label,
                'is_active' => $isActive,
                'allowed_by_plan' => $inPlan,
            ];
        }

        return $available;
    }

    public function getActiveModules(Company $company): array
    {
        return $company->modules()
            ->where('is_active', true)
            ->pluck('module')
            ->toArray();
    }

    private function getPlanFeatures(Company $company): array
    {
        $officePlanModules = $company->officePlan?->modules ?? [];

        if (count($officePlanModules) > 0) {
            return $officePlanModules;
        }

        $office = $company->office;

        if (! $office?->subscription?->plan) {
            return array_keys(Company::availableModules());
        }

        return $office->subscription->plan->features ?? [];
    }
}
