<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $companies = Company::with('office')->paginate(20);
        } elseif ($user->hasRole('company_user')) {
            $companies = $user->companies()->with('office')
                ->paginate($request->get('per_page', 200));
        } else {
            $companies = Company::with('office')
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
        ]);

        $user = $request->user();
        $validated['office_id'] = $user->office_id;

        if (!$user->hasRole('admin')) {
            $office = $user->office()->with('subscription.plan')->first();
            $maxCompanies = $office?->subscription?->plan?->max_companies;

            if ($maxCompanies !== null) {
                $currentCount = Company::where('office_id', $user->office_id)->count();
                if ($currentCount >= $maxCompanies) {
                    return response()->json([
                        'message' => "Limite de {$maxCompanies} empresa(s) atingido para o seu plano."
                    ], 422);
                }
            }
        }

        $company = Company::create($validated);

        // Assign the creator to the company
        $request->user()->companies()->attach($company->id);

        return response()->json($company, 201);
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        return response()->json($company->load(['office']));
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorizeCompany($request, $company);

        $validated = $request->validate([
            'razao_social' => ['sometimes', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'string', 'max:18', 'unique:companies,cnpj,' . $company->id],
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
        if (!openssl_pkcs12_read($pfxRaw, $certData, $request->input('senha'))) {
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
        if (!$request->user()->hasRole('admin') && $user->office_id !== $request->user()->office_id) {
            return response()->json(['message' => 'Usuário não pertence ao seu escritório.'], 403);
        }

        // Attach user to company
        $company->users()->syncWithoutDetaching([$user->id]);

        // Assign company_user role if not already has it
        if (!$user->hasRole('company_user')) {
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

    private function authorizeCompany(Request $request, Company $company): void
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($company->office_id !== $user->office_id) {
            abort(403, 'Sem permissão para acessar esta empresa.');
        }
    }
}
