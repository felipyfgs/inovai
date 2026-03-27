<?php

namespace App\Http\Controllers;

use App\Models\Nfse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NfseController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->nfses();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('chave', 'like', "%{$search}%")
                    ->orWhere('tomador_nome', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('data_inicio')) {
            $query->where('data_emissao', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->where('data_emissao', '<=', $request->input('data_fim'));
        }

        $query->orderBy('data_emissao', 'desc');

        return response()->json($this->paginate($query, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'pessoa_id' => ['nullable', 'exists:pessoas,id'],
            'serie' => ['nullable', 'string', 'max:3'],
            'data_emissao' => ['required', 'date'],
            'data_competencia' => ['required', 'date'],
            'natureza_operacao' => ['required', 'string', 'max:100'],
            'codigo_servico' => ['required', 'string', 'max:20'],
            'descricao_servico' => ['required', 'string'],
            'valor_servico' => ['required', 'numeric', 'min:0.01'],
            'valor_deducoes' => ['nullable', 'numeric', 'min:0'],
            'valor_desconto' => ['nullable', 'numeric', 'min:0'],
            'cnae' => ['nullable', 'string', 'max:7'],
            'cidade_ibge' => ['required', 'string', 'max:7'],
            'cidade' => ['required', 'string', 'max:100'],
            'uf' => ['required', 'string', 'size:2'],
            'tomador_nome' => ['nullable', 'string', 'max:255'],
            'tomador_cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'tomador_logradouro' => ['nullable', 'string', 'max:255'],
            'tomador_numero' => ['nullable', 'string', 'max:10'],
            'tomador_bairro' => ['nullable', 'string', 'max:255'],
            'tomador_cep' => ['nullable', 'string', 'max:9'],
            'tomador_municipio' => ['nullable', 'string', 'max:100'],
            'tomador_uf' => ['nullable', 'string', 'size:2'],
            'tomador_email' => ['nullable', 'email'],
            'tomador_telefone' => ['nullable', 'string', 'max:20'],
            'informacoes_adicionais' => ['nullable', 'string'],
            'itens' => ['nullable', 'array'],
            'itens.*.discriminacao' => ['required_with:itens', 'string', 'max:500'],
            'itens.*.quantidade' => ['nullable', 'numeric', 'min:0.0001'],
            'itens.*.unidade' => ['nullable', 'string', 'max:20'],
            'itens.*.valor_unitario' => ['required_with:itens', 'numeric', 'min:0.0001'],
            'itens.*.valor_total' => ['required_with:itens', 'numeric', 'min:0.01'],
        ]);

        $validated['company_id'] = $company->id;
        $validated['serie'] = $validated['serie'] ?? '1';
        $validated['status'] = 'rascunho';
        $validated['ambiente'] = $company->ambiente === 'producao' ? 1 : 2;

        $proximoNumero = $company->proximo_numero_nfse ?? 1;
        $validated['numero'] = $proximoNumero;

        $validated['valor_total'] = $validated['valor_servico']
            - ($validated['valor_deducoes'] ?? 0)
            - ($validated['valor_desconto'] ?? 0)
            + ($validated['valor_ir'] ?? 0)
            + ($validated['valor_inss'] ?? 0)
            + ($validated['valor_pis'] ?? 0)
            + ($validated['valor_cofins'] ?? 0)
            + ($validated['valor_csll'] ?? 0)
            + ($validated['valor_outras'] ?? 0);

        $nfse = Nfse::create($validated);

        if (!empty($validated['itens'])) {
            foreach ($validated['itens'] as $index => $item) {
                $nfse->itens()->create([
                    'numero_item' => $index + 1,
                    'discriminacao' => $item['discriminacao'],
                    'quantidade' => $item['quantidade'] ?? 1,
                    'unidade' => $item['unidade'] ?? null,
                    'valor_unitario' => $item['valor_unitario'],
                    'valor_total' => $item['valor_total'],
                ]);
            }
        }

        $company->update(['proximo_numero_nfse' => $proximoNumero + 1]);

        return response()->json($nfse->load(['itens', 'pessoa']), 201);
    }

    public function show(Nfse $nfse): JsonResponse
    {
        $this->authorizeNfse($nfse);

        return response()->json($nfse->load(['itens', 'pessoa', 'eventos']));
    }

    public function update(Request $request, Nfse $nfse): JsonResponse
    {
        $this->authorizeNfse($nfse);

        if ($nfse->status !== 'rascunho') {
            abort(422, 'Apenas NFS-e em rascunho pode ser alterada.');
        }

        $validated = $request->validate([
            'descricao_servico' => ['sometimes', 'string'],
            'valor_servico' => ['sometimes', 'numeric', 'min:0.01'],
            'valor_deducoes' => ['nullable', 'numeric', 'min:0'],
            'valor_desconto' => ['nullable', 'numeric', 'min:0'],
            'tomador_nome' => ['nullable', 'string', 'max:255'],
            'tomador_cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'informacoes_adicionais' => ['nullable', 'string'],
        ]);

        $nfse->update($validated);

        return response()->json($nfse);
    }

    public function destroy(Nfse $nfse): JsonResponse
    {
        $this->authorizeNfse($nfse);

        if ($nfse->status !== 'rascunho') {
            abort(422, 'Apenas NFS-e em rascunho pode ser removida.');
        }

        $nfse->delete();

        return response()->json(['message' => 'NFS-e removida com sucesso.']);
    }

    public function cancelar(Request $request, Nfse $nfse): JsonResponse
    {
        $this->authorizeNfse($nfse);

        $validated = $request->validate([
            'justificativa' => ['required', 'string', 'min:15', 'max:500'],
        ]);

        if ($nfse->status !== 'autorizada') {
            abort(422, 'Apenas NFS-e autorizada pode ser cancelada.');
        }

        $nfse->update([
            'status' => 'cancelada',
            'motivo' => $validated['justificativa'],
        ]);

        return response()->json($nfse);
    }

    private function authorizeNfse(Nfse $nfse): void
    {
        $company = app('current_company');
        if ($nfse->company_id !== $company->id) {
            abort(403, 'Sem permissão.');
        }
    }
}