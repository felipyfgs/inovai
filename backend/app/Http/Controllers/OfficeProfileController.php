<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $office = $request->user()->office;

        if (! $office) {
            return response()->json(['message' => 'Escritório não encontrado.'], 404);
        }

        $office->load(['subscription.plan', 'users' => fn ($q) => $q->where('is_active', true)]);

        return response()->json($office);
    }

    public function update(Request $request): JsonResponse
    {
        $office = $request->user()->office;

        if (! $office) {
            return response()->json(['message' => 'Escritório não encontrado.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'string', 'max:18', 'unique:offices,cnpj,'.$office->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'ie' => ['nullable', 'string', 'max:20'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'municipio_ibge' => ['nullable', 'string', 'max:10'],
            'uf' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'notes' => ['nullable', 'string'],
        ]);

        $office->update($validated);

        return response()->json($office->load(['subscription.plan', 'users' => fn ($q) => $q->where('is_active', true)]));
    }
}
