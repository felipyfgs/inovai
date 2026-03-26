<?php

namespace App\Http\Controllers;

use App\Models\Transportadora;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportadoraController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->transportadoras();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('razao_social', 'ilike', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%");
            });
        }

        return response()->json($this->paginate($query, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'razao_social' => ['required', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'ie' => ['nullable', 'string', 'max:20'],
            'rntrc' => ['nullable', 'string', 'max:20'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:10'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'municipio_ibge' => ['nullable', 'string', 'max:7'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $validated['company_id'] = $company->id;
        $transportadora = Transportadora::create($validated);

        return response()->json($transportadora, 201);
    }

    public function show(Transportadora $transportadora): JsonResponse
    {
        $this->authorize_resource($transportadora);

        return response()->json($transportadora->load('veiculos'));
    }

    public function update(Request $request, Transportadora $transportadora): JsonResponse
    {
        $this->authorize_resource($transportadora);

        $validated = $request->validate([
            'razao_social' => ['sometimes', 'string', 'max:255'],
            'fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'ie' => ['nullable', 'string', 'max:20'],
            'rntrc' => ['nullable', 'string', 'max:20'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:10'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'municipio_ibge' => ['nullable', 'string', 'max:7'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $transportadora->update($validated);

        return response()->json($transportadora);
    }

    public function destroy(Transportadora $transportadora): JsonResponse
    {
        $this->authorize_resource($transportadora);
        $transportadora->delete();

        return response()->json(['message' => 'Transportadora removida com sucesso.']);
    }

    private function authorize_resource(Transportadora $transportadora): void
    {
        $company = app('current_company');
        if ($transportadora->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}
