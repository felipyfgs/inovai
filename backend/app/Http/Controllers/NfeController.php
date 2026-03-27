<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNfeRequest;
use App\Jobs\CancelDocumentJob;
use App\Jobs\EventDocumentJob;
use App\Jobs\SignDocumentJob;
use App\Jobs\TransmitDocumentJob;
use App\Models\Nfe;
use App\Services\NfeService;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NfeController extends Controller
{
    use HasPagination;

    public function __construct(
        private NfeService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $company = app('current_company');

        $query = $company->nfes();

        if ($request->filled('modelo')) {
            $query->where('modelo', $request->integer('modelo'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->integer('pessoa_id'));
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_emissao', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_emissao', '<=', $request->input('data_fim'));
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

    public function store(StoreNfeRequest $request): JsonResponse
    {
        $company = app('current_company');

        $nota = $this->service->create($request->validated(), $company);

        return response()->json($nota, 201);
    }

    public function show(Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        $nfe->load('itens.produto', 'pessoa', 'transportadora', 'eventos');

        return response()->json($nfe);
    }

    public function update(StoreNfeRequest $request, Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if (! in_array($nfe->status, ['rascunho', 'rejeitada'])) {
            abort(422, 'Nota fiscal so pode ser editada em rascunho ou rejeitada.');
        }

        $nota = $this->service->update($nfe, $request->validated());

        return response()->json($nota);
    }

    public function destroy(Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if (! in_array($nfe->status, ['rascununho'])) {
            abort(422, 'Nota fiscal so pode ser excluida em rascunho.');
        }

        $nfe->delete();

        return response()->json(['message' => 'Nota fiscal removida com sucesso.']);
    }

    public function sign(Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if (! in_array($nfe->status, ['rascunho', 'rejeitada'])) {
            abort(422, 'Nota fiscal so pode ser assinada em rascunho ou rejeitada.');
        }

        SignDocumentJob::dispatch(
            $nfe->isNfce() ? 'nfce' : 'nfe',
            $nfe->id
        );

        $nfe->update(['status' => 'assinada']);

        return response()->json([
            'message' => 'Nota fiscal enviada para assinatura.',
        ]);
    }

    public function transmit(Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if ($nfe->status !== 'assinada') {
            abort(422, 'Nota fiscal deve estar assinada para transmitir.');
        }

        TransmitDocumentJob::dispatch(
            $nfe->isNfce() ? 'nfce' : 'nfe',
            $nfe->id
        );

        return response()->json([
            'message' => 'Nota fiscal enviada para transmissao.',
        ]);
    }

    public function cancel(Request $request, Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if ($nfe->status !== 'autorizada') {
            abort(422, 'Nota fiscal deve estar autorizada para cancelar.');
        }

        $request->validate([
            'justificativa' => ['required', 'string', 'min:15', 'max:255'],
        ]);

        CancelDocumentJob::dispatch(
            $nfe->isNfce() ? 'nfce' : 'nfe',
            $nfe->id,
            $request->input('justificativa')
        );

        return response()->json([
            'message' => 'Solicitacao de cancelamento enviada.',
        ]);
    }

    public function cartaCorrecao(Request $request, Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if ($nfe->status !== 'autorizada') {
            abort(422, 'Nota fiscal deve estar autorizada para enviar CC-e.');
        }

        $request->validate([
            'correcao' => ['required', 'array', 'min:1'],
            'correcao.*.grupo' => ['required', 'string'],
            'correcao.*.campo' => ['required', 'string'],
            'correcao.*.valor_anterior' => ['required', 'string'],
            'correcao.*.valor_novo' => ['required', 'string'],
        ]);

        EventDocumentJob::dispatch(
            'nfe',
            $nfe->id,
            'cce',
            ['correcao' => $request->input('correcao')]
        );

        return response()->json([
            'message' => 'Carta de correcao enviada.',
        ]);
    }

    public function getXml(Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if (! $nfe->xml_autorizado) {
            abort(404, 'XML nao disponivel.');
        }

        return response()->json([
            'xml' => $nfe->xml_autorizado,
        ]);
    }

    public function getDanfe(Nfe $nfe): JsonResponse
    {
        $this->authorizeNfe($nfe);

        if (! $nfe->xml_autorizado) {
            abort(404, 'DANFE nao disponivel. Nota nao autorizada.');
        }

        return response()->json([
            'message' => 'Geracao de DANFE disponivel via endpoint dedicado.',
        ]);
    }

    private function authorizeNfe(Nfe $nfe): void
    {
        $company = app('current_company');
        if ($nfe->company_id !== $company->id) {
            abort(403, 'Sem permissao.');
        }
    }
}
