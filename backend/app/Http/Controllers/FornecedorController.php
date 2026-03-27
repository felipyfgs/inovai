<?php

namespace App\Http\Controllers;

use App\Models\Pessoa;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->pessoas()
            ->where(function ($q) {
                $q->where('tipo', 'fornecedor')
                    ->orWhere('tipo', 'ambos');
            });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('razao_social', 'ilike', "%{$search}%")
                    ->orWhere('fantasia', 'ilike', "%{$search}%")
                    ->orWhere('cpf_cnpj', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        $query->orderBy('razao_social');

        return response()->json($this->paginate($query, $request));
    }

    public function show(Pessoa $pessoa): JsonResponse
    {
        $this->authorizePessoa($pessoa);

        return response()->json($pessoa);
    }

    public function update(Request $request, Pessoa $pessoa): JsonResponse
    {
        $this->authorizePessoa($pessoa);

        $validated = $request->validate([
            'condicao_pagamento' => ['nullable', 'string', 'max:100'],
            'prazo_entrega' => ['nullable', 'integer', 'min:0'],
            'avaliacao' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $pessoa->update($validated);

        return response()->json($pessoa);
    }

    private function authorizePessoa(Pessoa $pessoa): void
    {
        $company = app('current_company');
        if ($pessoa->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}
