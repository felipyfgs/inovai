<?php

namespace App\Http\Controllers;

use App\Models\Pessoa;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PessoaController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->pessoas();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('razao_social', 'ilike', "%{$search}%")
                    ->orWhere('fantasia', 'ilike', "%{$search}%")
                    ->orWhere('cpf_cnpj', 'like', "%{$search}%");
            });
        }

        return response()->json($this->paginate($query, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'tipo' => ['required', 'string', 'in:cliente,fornecedor,ambos'],
            'tipo_pessoa' => ['required', 'string', 'in:PF,PJ'],
            'razao_social' => ['required', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'ie' => ['nullable', 'string', 'max:20'],
            'im' => ['nullable', 'string', 'max:20'],
            'ind_ie' => ['nullable', 'integer', 'in:1,2,9'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'municipio_ibge' => ['nullable', 'string', 'max:7'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = $company->id;

        $pessoa = Pessoa::create($validated);

        return response()->json($pessoa, 201);
    }

    public function show(Request $request, Pessoa $pessoa): JsonResponse
    {
        $this->authorizePessoa($pessoa);

        return response()->json($pessoa);
    }

    public function update(Request $request, Pessoa $pessoa): JsonResponse
    {
        $this->authorizePessoa($pessoa);

        $validated = $request->validate([
            'tipo' => ['sometimes', 'string', 'in:cliente,fornecedor,ambos'],
            'tipo_pessoa' => ['sometimes', 'string', 'in:PF,PJ'],
            'razao_social' => ['sometimes', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'ie' => ['nullable', 'string', 'max:20'],
            'im' => ['nullable', 'string', 'max:20'],
            'ind_ie' => ['nullable', 'integer', 'in:1,2,9'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'municipio_ibge' => ['nullable', 'string', 'max:7'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $pessoa->update($validated);

        return response()->json($pessoa);
    }

    public function destroy(Pessoa $pessoa): JsonResponse
    {
        $this->authorizePessoa($pessoa);
        $pessoa->delete();

        return response()->json(['message' => 'Pessoa removida com sucesso.']);
    }

    private function authorizePessoa(Pessoa $pessoa): void
    {
        $company = app('current_company');
        if ($pessoa->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}
