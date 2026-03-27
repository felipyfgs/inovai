<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Office;
use App\Models\OfficePlan;
use App\Models\User;
use App\Services\CompanyModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyModuleService $moduleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $officeId = $request->header('X-Office-Id');

        if ($user->hasRole('admin')) {
            if ($officeId) {
                $companies = Company::with('office', 'modules')
                    ->where('office_id', $officeId)
                    ->paginate($request->get('per_page', 200));
            } else {
                $companies = Company::with('office', 'modules')->paginate(20);
            }
        } elseif ($user->hasRole('company_user')) {
            $companies = $user->companies()->with('office', 'modules')
                ->paginate($request->get('per_page', 200));
        } else {
            $companies = Company::with('office', 'modules')
                ->where('office_id', $user->office_id)
                ->paginate($request->get('per_page', 200));
        }

        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razao_social' => ['required', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:18', 'unique:companies,cnpj'],
            'ie' => ['nullable', 'string', 'max:20'],
            'im' => ['nullable', 'string', 'max:20'],
            'crt' => ['nullable', 'integer', 'in:1,2,3'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'municipio_ibge' => ['nullable', 'string', 'max:7'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'ambiente' => ['nullable', 'string', 'in:homologacao,producao'],
            'profile_id' => ['nullable', 'exists:office_plans,id'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'confirmed', Password::defaults()],
            'owner_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $officeId = $request->header('X-Office-Id');
        $validated['office_id'] = $officeId ?? $user->office_id;

        if ($user->hasRole('admin') && $officeId) {
            $office = Office::with('subscription.plan')->find($officeId);
            $maxCompanies = $office?->subscription?->plan?->max_companies;

            if ($maxCompanies !== null) {
                $currentCount = Company::where('office_id', $officeId)->count();
                if ($currentCount >= $maxCompanies) {
                    return response()->json([
                        'message' => "Limite de {$maxCompanies} empresa(s) atingido para o seu plano.",
                    ], 422);
                }
            }
        } elseif (! $user->hasRole('admin')) {
            $office = $user->office()->with('subscription.plan')->first();
            $maxCompanies = $office?->subscription?->plan?->max_companies;

            if ($maxCompanies !== null) {
                $currentCount = Company::where('office_id', $user->office_id)->count();
                if ($currentCount >= $maxCompanies) {
                    return response()->json([
                        'message' => "Limite de {$maxCompanies} empresa(s) atingido para o seu plano.",
                    ], 422);
                }
            }
        }

        $officePlanId = $validated['profile_id'] ?? null;
        $ownerName = $validated['owner_name'];
        $ownerEmail = $validated['owner_email'];
        $ownerPassword = $validated['owner_password'];
        $ownerPhone = $validated['owner_phone'] ?? null;

        unset(
            $validated['profile_id'],
            $validated['owner_name'],
            $validated['owner_email'],
            $validated['owner_password'],
            $validated['owner_password_confirmation'],
            $validated['owner_phone']
        );

        $validated['office_plan_id'] = $officePlanId;

        $company = DB::transaction(function () use ($validated, $ownerName, $ownerEmail, $ownerPassword, $ownerPhone, $officePlanId, $user) {
            $company = Company::create($validated);

            $owner = User::create([
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => Hash::make($ownerPassword),
                'phone' => $ownerPhone,
                'office_id' => $validated['office_id'],
                'must_change_password' => true,
            ]);
            $owner->assignRole('company_user');

            $company->users()->attach($owner->id);
            $user->companies()->attach($company->id);

            if ($officePlanId) {
                $officePlan = OfficePlan::find($officePlanId);
                if ($officePlan && $officePlan->office_id == $validated['office_id']) {
                    foreach ($officePlan->modules as $module) {
                        $company->modules()->create([
                            'module' => $module,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            return $company;
        });

        return response()->json($company->load('modules'), 201);
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        return response()->json($company->load(['office', 'modules']));
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $validated = $request->validate([
            'razao_social' => ['sometimes', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'string', 'max:18', 'unique:companies,cnpj,'.$company->id],
            'ie' => ['nullable', 'string', 'max:20'],
            'im' => ['nullable', 'string', 'max:20'],
            'crt' => ['nullable', 'integer', 'in:1,2,3'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'municipio_ibge' => ['nullable', 'string', 'max:7'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'ambiente' => ['nullable', 'string', 'in:homologacao,producao'],
            'csc_id' => ['nullable', 'string', 'max:10'],
            'csc_token' => ['nullable', 'string'],
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    public function destroy(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);
        $company->delete();

        return response()->json(['message' => 'Empresa removida com sucesso.']);
    }

    public function uploadCertificado(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $request->validate([
            'certificado' => ['required', 'file', 'max:10240'],
            'senha' => ['required', 'string'],
        ]);

        $pfxContent = base64_encode(file_get_contents($request->file('certificado')->path()));

        // Validate certificate
        $certData = [];
        $pfxRaw = base64_decode($pfxContent);
        if (! openssl_pkcs12_read($pfxRaw, $certData, $request->input('senha'))) {
            return response()->json(['message' => 'Certificado inválido ou senha incorreta.'], 422);
        }

        // Get expiration date
        $certInfo = openssl_x509_parse($certData['cert']);
        $validade = date('Y-m-d', $certInfo['validTo_time_t']);

        $company->update([
            'certificado_pfx' => $pfxContent,
            'certificado_senha' => $request->input('senha'),
            'certificado_validade' => $validade,
        ]);

        return response()->json([
            'message' => 'Certificado enviado com sucesso.',
            'validade' => $validade,
        ]);
    }

    /**
     * List users attached to a company
     */
    public function users(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $users = $company->users()->with('roles')->get();

        return response()->json($users);
    }

    /**
     * Attach a user to a company
     */
    public function attachUser(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Check if user belongs to same office (for office_user)
        if (! $request->user()->hasRole('admin') && $user->office_id !== $request->user()->office_id) {
            return response()->json(['message' => 'Usuário não pertence ao seu escritório.'], 403);
        }

        // Attach user to company
        $company->users()->syncWithoutDetaching([$user->id]);

        // Assign company_user role if not already has it
        if (! $user->hasRole('company_user')) {
            $user->assignRole('company_user');
        }

        return response()->json(['message' => 'Usuário vinculado à empresa.']);
    }

    /**
     * Detach a user from a company
     */
    public function detachUser(Request $request, Company $company, User $user): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $company->users()->detach($user->id);

        return response()->json(['message' => 'Usuário desvinculado da empresa.']);
    }

    public function updateOwner(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $validated = $request->validate([
            'owner_name' => ['sometimes', 'string', 'max:255'],
            'owner_email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['nullable', 'confirmed', Password::defaults()],
            'owner_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $owner = $company->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'company_user'))
            ->first();

        if (! $owner) {
            return response()->json(['message' => 'Nenhum proprietário vinculado a esta empresa.'], 404);
        }

        $updateData = array_filter([
            'name' => $validated['owner_name'] ?? null,
            'email' => $validated['owner_email'] ?? null,
            'phone' => $validated['owner_phone'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($validated['owner_password'])) {
            $updateData['password'] = Hash::make($validated['owner_password']);
            $updateData['must_change_password'] = true;
        }

        $owner->update($updateData);

        return response()->json(['message' => 'Dados do proprietário atualizados.']);
    }

    private function authorizeCompany(Request $request, Company $company): void
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasAnyRole(['office_user', 'accountant'])) {
            if ($company->office_id !== $user->office_id) {
                abort(403, 'Sem permissão para acessar esta empresa.');
            }

            return;
        }

        // company_user: must be attached to the company
        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            abort(403, 'Sem permissão para acessar esta empresa.');
        }
    }
}
