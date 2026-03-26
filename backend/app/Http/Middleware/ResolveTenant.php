<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $companyId = $request->header('X-Company-Id');

        // If no company header, try to auto-select single company
        if (! $companyId && ! $user->hasRole('admin')) {
            $companyId = $this->getAutoSelectedCompanyId($user);
        }

        if ($companyId) {
            $cacheKey = "tenant:{$user->id}:{$companyId}";

            $company = Cache::remember($cacheKey, 300, function () use ($user, $companyId) {
                if ($user->hasRole('admin')) {
                    return Company::find($companyId);
                }

                if ($user->hasAnyRole(['office_user', 'accountant'])) {
                    return Company::where('id', $companyId)
                        ->where('office_id', $user->office_id)
                        ->first();
                }

                return $user->companies()->where('companies.id', $companyId)->first();
            });

            if (! $company) {
                return response()->json(['message' => 'Sem permissão para acessar esta empresa.'], 403);
            }

            $request->merge(['current_company' => $company]);
            app()->instance('current_company', $company);
        }

        if ($user->office_id) {
            app()->instance('current_office', $user->office);
        }

        return $next($request);
    }

    /**
     * Auto-select company if user has only one
     */
    private function getAutoSelectedCompanyId($user): ?int
    {
        // For office_user, check companies in their office
        if ($user->hasAnyRole(['office_user', 'accountant'])) {
            $companiesCount = Company::where('office_id', $user->office_id)->count();
            if ($companiesCount === 1) {
                return Company::where('office_id', $user->office_id)->value('id');
            }
        }

        // For company_user, check their attached companies
        if ($user->hasRole('company_user')) {
            $companiesCount = $user->companies()->count();
            if ($companiesCount === 1) {
                return $user->companies()->first()->id;
            }
        }

        return null;
    }
}
