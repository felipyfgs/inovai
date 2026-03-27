<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Models\ContaParcela;
use App\Services\ContaService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContaController extends Controller
{
    use HasPagination;

    public function __construct(private ContaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');
        $query = $this->service->getQuery($company, $request);

        return response()->json($this->paginate($query, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => ['required', 'in:pagar,receber'],
            'pessoa_id' => ['nullable', 'exists:pessoas,id'],
            'descricao' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:50'],
            'pedido_id' => ['nullable', 'exists:pedidos,id'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'data_emissao' => ['required', 'date'],
            'data_vencimento' => ['required', 'date'],
            'valor_original' => ['required', 'numeric', 'min:0.01'],
            'observacoes' => ['nullable', 'string'],
            'parcelas' => ['nullable', 'array'],
            'parcelas.*.data_vencimento' => ['required_with:parcelas', 'date'],
            'parcelas.*.valor' => ['required_with:parcelas', 'numeric', 'min:0.01'],
        ]);

        $conta = $this->service->create($validated);

        return response()->json($conta->load('pessoa'), 201);
    }

    public function show(Conta $conta): JsonResponse
    {
        $this->authorizeConta($conta);

        return response()->json($conta->load(['pessoa', 'parcelas', 'pedido']));
    }

    public function update(Request $request, Conta $conta): JsonResponse
    {
        $this->authorizeConta($conta);

        if ($conta->status === 'pago') {
            abort(422, 'Não é possível alterar uma conta já paga.');
        }

        $validated = $request->validate([
            'descricao' => ['sometimes', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:50'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'data_vencimento' => ['sometimes', 'date'],
            'valor_original' => ['sometimes', 'numeric', 'min:0.01'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $conta = $this->service->update($conta, $validated);

        return response()->json($conta);
    }

    public function destroy(Conta $conta): JsonResponse
    {
        $this->authorizeConta($conta);

        if ($conta->status === 'pago') {
            abort(422, 'Não é possível remover uma conta já paga.');
        }

        $conta->delete();

        return response()->json(['message' => 'Conta removida com sucesso.']);
    }

    public function baixarParcela(Request $request, ContaParcela $parcela): JsonResponse
    {
        $conta = $parcela->conta;
        $this->authorizeConta($conta);

        $validated = $request->validate([
            'valor' => ['required', 'numeric', 'min:0.01'],
            'valor_desconto' => ['nullable', 'numeric', 'min:0'],
            'valor_juros' => ['nullable', 'numeric', 'min:0'],
            'valor_multa' => ['nullable', 'numeric', 'min:0'],
            'forma_pagamento' => ['nullable', 'string', 'max:50'],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ]);

        $parcela = $this->service->baixarParcela($parcela, $validated);

        return response()->json($parcela->load('conta'));
    }

    public function estornarParcela(ContaParcela $parcela): JsonResponse
    {
        $conta = $parcela->conta;
        $this->authorizeConta($conta);

        $parcela = $this->service->estornarBaixa($parcela);

        return response()->json($parcela->load('conta'));
    }

    public function cancelar(Conta $conta): JsonResponse
    {
        $this->authorizeConta($conta);

        $conta = $this->service->cancelar($conta);

        return response()->json($conta);
    }

    public function resumo(): JsonResponse
    {
        $company = app('current_company');

        return response()->json($this->service->getResumo($company));
    }

    private function authorizeConta(Conta $conta): void
    {
        $company = app('current_company');
        if ($conta->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}
