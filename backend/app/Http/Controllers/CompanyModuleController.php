<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyModuleController extends Controller
{
    public function __construct(
        private CompanyModuleService $moduleService
    ) {}

    public function index(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        return response()->json($this->moduleService->getAvailableModules($company));
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*' => ['string'],
        ]);

        $result = $this->moduleService->syncModules($company, $validated['modules']);

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json($result);
    }

    private function authorizeCompany(Request $request, Company $company): void
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasAnyRole(['office_user', 'accountant'])) {
            if ($company->office_id !== $user->office_id) {
                abort(403, 'Sem permissão.');
            }

            return;
        }

        abort(403, 'Sem permissão.');
    }
}
