<?php

namespace App\Http\Controllers;

use App\Jobs\CancelDocumentJob;
use App\Jobs\EventDocumentJob;
use App\Jobs\SignDocumentJob;
use App\Jobs\TransmitDocumentJob;
use App\Models\Mdfe;
use App\Services\MdfeService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MdfeController extends Controller
{
    use HasPagination;

    public function __construct(
        private MdfeService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->mdfes();

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
            'veiculo_id' => ['nullable', 'exists:veiculos,id'],
            'motorista_id' => ['nullable', 'exists:motoristas,id'],
            'serie' => ['nullable', 'integer'],
            'numero' => ['nullable', 'integer'],
            'modal' => ['nullable', 'integer', 'in:1'],
            'data_emissao' => ['nullable', 'date'],
            'uf_carregamento' => ['required', 'string', 'size:2'],
            'uf_descarregamento' => ['required', 'string', 'size:2'],
            'municipio_carregamento' => ['nullable', 'string', 'max:255'],
            'municipio_carregamento_ibge' => ['nullable', 'string', 'max:7'],
            'municipio_descarregamento' => ['nullable', 'string', 'max:255'],
            'municipio_descarregamento_ibge' => ['nullable', 'string', 'max:7'],
            'veiculo_placa' => ['nullable', 'string', 'max:8'],
            'motorista_cpf' => ['nullable', 'string', 'max:14'],
            'motorista_nome' => ['nullable', 'string', 'max:255'],
            'valor_carga' => ['nullable', 'numeric', 'min:0'],
            'peso_bruto' => ['nullable', 'numeric', 'min:0'],
            'uf_percurso' => ['nullable', 'array'],
            'uf_percurso.*' => ['string', 'size:2'],
            'informacoes_adicionais' => ['nullable', 'string'],
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*.tipo' => ['required', 'in:nfe,cte'],
            'documentos.*.chave' => ['required', 'string', 'size:44'],
        ]);

        $mdfe = $this->service->create($validated, $company);

        return response()->json($mdfe, 201);
    }

    public function show(Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        $mdfe->load('documentos', 'veiculo', 'motorista', 'eventos');

        return response()->json($mdfe);
    }

    public function update(Request $request, Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        if (! in_array($mdfe->status, ['rascunho', 'rejeitada'])) {
            abort(422, 'MDF-e so pode ser editado em rascunho ou rejeitado.');
        }

        $validated = $request->validate([
            'veiculo_id' => ['nullable', 'exists:veiculos,id'],
            'motorista_id' => ['nullable', 'exists:motoristas,id'],
            'modal' => ['nullable', 'integer', 'in:1'],
            'data_emissao' => ['nullable', 'date'],
            'uf_carregamento' => ['nullable', 'string', 'size:2'],
            'uf_descarregamento' => ['nullable', 'string', 'size:2'],
            'municipio_carregamento' => ['nullable', 'string', 'max:255'],
            'municipio_carregamento_ibge' => ['nullable', 'string', 'max:7'],
            'municipio_descarregamento' => ['nullable', 'string', 'max:255'],
            'municipio_descarregamento_ibge' => ['nullable', 'string', 'max:7'],
            'veiculo_placa' => ['nullable', 'string', 'max:8'],
            'motorista_cpf' => ['nullable', 'string', 'max:14'],
            'motorista_nome' => ['nullable', 'string', 'max:255'],
            'valor_carga' => ['nullable', 'numeric', 'min:0'],
            'peso_bruto' => ['nullable', 'numeric', 'min:0'],
            'uf_percurso' => ['nullable', 'array'],
            'uf_percurso.*' => ['string', 'size:2'],
            'informacoes_adicionais' => ['nullable', 'string'],
            'documentos' => ['nullable', 'array', 'min:1'],
            'documentos.*.tipo' => ['required_with:documentos', 'in:nfe,cte'],
            'documentos.*.chave' => ['required_with:documentos', 'string', 'size:44'],
        ]);

        $mdfe = $this->service->update($mdfe, $validated);

        return response()->json($mdfe);
    }

    public function destroy(Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        if (! in_array($mdfe->status, ['rascunho'])) {
            abort(422, 'MDF-e so pode ser excluido em rascunho.');
        }

        $mdfe->delete();

        return response()->json(['message' => 'MDF-e removido com sucesso.']);
    }

    public function sign(Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        if (! in_array($mdfe->status, ['rascunho', 'rejeitada'])) {
            abort(422, 'MDF-e so pode ser assinado em rascunho ou rejeitado.');
        }

        SignDocumentJob::dispatch('mdfe', $mdfe->id);

        return response()->json(['message' => 'MDF-e enviado para assinatura.']);
    }

    public function transmit(Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        if ($mdfe->status !== 'assinada') {
            abort(422, 'MDF-e deve estar assinado para transmitir.');
        }

        TransmitDocumentJob::dispatch('mdfe', $mdfe->id);

        return response()->json(['message' => 'MDF-e enviado para transmissao.']);
    }

    public function cancel(Request $request, Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        if ($mdfe->status !== 'autorizada') {
            abort(422, 'MDF-e deve estar autorizado para cancelar.');
        }

        $request->validate([
            'justificativa' => ['required', 'string', 'min:15', 'max:255'],
        ]);

        CancelDocumentJob::dispatch('mdfe', $mdfe->id, $request->input('justificativa'));

        return response()->json(['message' => 'Solicitacao de cancelamento enviada.']);
    }

    public function encerrar(Request $request, Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        if ($mdfe->status !== 'autorizada') {
            abort(422, 'MDF-e deve estar autorizado para encerrar.');
        }

        $request->validate([
            'uf' => ['required', 'string', 'size:2'],
            'municipio' => ['required', 'string', 'max:255'],
            'municipio_ibge' => ['required', 'string', 'max:7'],
        ]);

        EventDocumentJob::dispatch('mdfe', $mdfe->id, 'encerramento', [
            'uf' => $request->input('uf'),
            'municipio' => $request->input('municipio'),
            'municipio_ibge' => $request->input('municipio_ibge'),
        ]);

        return response()->json(['message' => 'Encerramento do MDF-e enviado.']);
    }

    public function getXml(Mdfe $mdfe): JsonResponse
    {
        $this->authorizeMdfe($mdfe);

        if (! $mdfe->xml_autorizado) {
            abort(404, 'XML nao disponivel.');
        }

        return response()->json(['xml' => $mdfe->xml_autorizado]);
    }

    private function authorizeMdfe(Mdfe $mdfe): void
    {
        $company = app('current_company');
        if ($mdfe->company_id !== $company->id) {
            abort(403, 'Sem permissao.');
        }
    }
}
