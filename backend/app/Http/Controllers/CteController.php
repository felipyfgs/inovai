<?php

namespace App\Http\Controllers;

use App\Jobs\CancelDocumentJob;
use App\Jobs\SignDocumentJob;
use App\Jobs\TransmitDocumentJob;
use App\Models\Cte;
use App\Services\CteService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CteController extends Controller
{
    use HasPagination;

    public function __construct(
        private CteService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->ctes();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('chave', 'like', "%{$search}%")
                    ->orWhere('numero', (int) $search);
            });
        }

        return response()->json($this->paginate($query, $request));
    }

    public function store(Request $request): JsonResponse
    {
        $company = app('current_company');

        $validated = $request->validate([
            'remetente_id' => ['nullable', 'exists:pessoas,id'],
            'destinatario_id' => ['nullable', 'exists:pessoas,id'],
            'expedidor_id' => ['nullable', 'exists:pessoas,id'],
            'recebedor_id' => ['nullable', 'exists:pessoas,id'],
            'tomador_id' => ['nullable', 'exists:pessoas,id'],
            'tomador_tipo' => ['nullable', 'integer', 'in:0,1,2,3'],
            'serie' => ['nullable', 'integer'],
            'numero' => ['nullable', 'integer'],
            'cfop' => ['nullable', 'string', 'max:4'],
            'natureza_operacao' => ['nullable', 'string', 'max:60'],
            'modal' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'data_emissao' => ['nullable', 'date'],
            'valor_servico' => ['nullable', 'numeric', 'min:0'],
            'valor_receber' => ['nullable', 'numeric', 'min:0'],
            'valor_icms' => ['nullable', 'numeric', 'min:0'],
            'valor_total' => ['nullable', 'numeric', 'min:0'],
            'uf_inicio' => ['nullable', 'string', 'size:2'],
            'municipio_inicio' => ['nullable', 'string', 'max:255'],
            'municipio_inicio_ibge' => ['nullable', 'string', 'max:7'],
            'uf_fim' => ['nullable', 'string', 'size:2'],
            'municipio_fim' => ['nullable', 'string', 'max:255'],
            'municipio_fim_ibge' => ['nullable', 'string', 'max:7'],
            'informacoes_adicionais' => ['nullable', 'string'],
            'chaves_nfe' => ['nullable', 'array'],
            'chaves_nfe.*' => ['nullable', 'string', 'size:44'],
        ]);

        $cte = $this->service->create($validated, $company);

        return response()->json($cte, 201);
    }

    public function show(Cte $cte): JsonResponse
    {
        $this->authorizeCte($cte);

        $cte->load('nfes', 'remetente', 'destinatario', 'expedidor', 'recebedor', 'tomador', 'eventos');

        return response()->json($cte);
    }

    public function update(Request $request, Cte $cte): JsonResponse
    {
        $this->authorizeCte($cte);

        if (! in_array($cte->status, ['rascunho', 'rejeitada'])) {
            abort(422, 'CT-e so pode ser editado em rascunho ou rejeitado.');
        }

        $validated = $request->validate([
            'remetente_id' => ['nullable', 'exists:pessoas,id'],
            'destinatario_id' => ['nullable', 'exists:pessoas,id'],
            'expedidor_id' => ['nullable', 'exists:pessoas,id'],
            'recebedor_id' => ['nullable', 'exists:pessoas,id'],
            'tomador_id' => ['nullable', 'exists:pessoas,id'],
            'tomador_tipo' => ['nullable', 'integer', 'in:0,1,2,3'],
            'cfop' => ['nullable', 'string', 'max:4'],
            'natureza_operacao' => ['nullable', 'string', 'max:60'],
            'modal' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'data_emissao' => ['nullable', 'date'],
            'valor_servico' => ['nullable', 'numeric', 'min:0'],
            'valor_receber' => ['nullable', 'numeric', 'min:0'],
            'valor_icms' => ['nullable', 'numeric', 'min:0'],
            'valor_total' => ['nullable', 'numeric', 'min:0'],
            'uf_inicio' => ['nullable', 'string', 'size:2'],
            'municipio_inicio' => ['nullable', 'string', 'max:255'],
            'municipio_inicio_ibge' => ['nullable', 'string', 'max:7'],
            'uf_fim' => ['nullable', 'string', 'size:2'],
            'municipio_fim' => ['nullable', 'string', 'max:255'],
            'municipio_fim_ibge' => ['nullable', 'string', 'max:7'],
            'informacoes_adicionais' => ['nullable', 'string'],
            'chaves_nfe' => ['nullable', 'array'],
            'chaves_nfe.*' => ['nullable', 'string', 'size:44'],
        ]);

        $cte = $this->service->update($cte, $validated);

        return response()->json($cte);
    }

    public function destroy(Cte $cte): JsonResponse
    {
        $this->authorizeCte($cte);

        if (! in_array($cte->status, ['rascunho'])) {
            abort(422, 'CT-e so pode ser excluido em rascunho.');
        }

        $cte->delete();

        return response()->json(['message' => 'CT-e removido com sucesso.']);
    }

    public function sign(Cte $cte): JsonResponse
    {
        $this->authorizeCte($cte);

        if (! in_array($cte->status, ['rascunho', 'rejeitada'])) {
            abort(422, 'CT-e so pode ser assinado em rascunho ou rejeitado.');
        }

        SignDocumentJob::dispatch('cte', $cte->id);

        return response()->json(['message' => 'CT-e enviado para assinatura.']);
    }

    public function transmit(Cte $cte): JsonResponse
    {
        $this->authorizeCte($cte);

        if ($cte->status !== 'assinada') {
            abort(422, 'CT-e deve estar assinado para transmitir.');
        }

        TransmitDocumentJob::dispatch('cte', $cte->id);

        return response()->json(['message' => 'CT-e enviado para transmissao.']);
    }

    public function cancel(Request $request, Cte $cte): JsonResponse
    {
        $this->authorizeCte($cte);

        if ($cte->status !== 'autorizada') {
            abort(422, 'CT-e deve estar autorizado para cancelar.');
        }

        $request->validate([
            'justificativa' => ['required', 'string', 'min:15', 'max:255'],
        ]);

        CancelDocumentJob::dispatch('cte', $cte->id, $request->input('justificativa'));

        return response()->json(['message' => 'Solicitacao de cancelamento enviada.']);
    }

    public function getXml(Cte $cte): JsonResponse
    {
        $this->authorizeCte($cte);

        if (! $cte->xml_autorizado) {
            abort(404, 'XML nao disponivel.');
        }

        return response()->json(['xml' => $cte->xml_autorizado]);
    }

    private function authorizeCte(Cte $cte): void
    {
        $company = app('current_company');
        if ($cte->company_id !== $company->id) {
            abort(403, 'Sem permissao.');
        }
    }
}
