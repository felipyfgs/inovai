<?php

namespace App\Jobs;

use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\Nfe;
use App\Services\SefazService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use NFePHP\NFe\Common\Standardize;

class CancelDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = [30, 60, 120];

    public function __construct(
        public string $tipo,
        public int $documentId,
        public string $justificativa
    ) {}

    public function handle(SefazService $sefazService): void
    {
        match ($this->tipo) {
            'nfe', 'nfce' => $this->cancelNfe($sefazService),
            'cte' => $this->cancelCte($sefazService),
            'mdfe' => $this->cancelMdfe($sefazService),
            default => throw new \InvalidArgumentException("Tipo de documento invalido: {$this->tipo}"),
        };
    }

    private function cancelNfe(SefazService $sefazService): void
    {
        $nota = Nfe::findOrFail($this->documentId);

        if ($nota->status !== 'autorizada') {
            return;
        }

        $company = $nota->company;
        $tools = $sefazService->makeNfeTools($company);

        try {
            $resp = $tools->sefazCancela(
                $nota->chave,
                $this->justificativa,
                $nota->protocolo
            );

            $std = new Standardize;
            $stdResp = $std->toStd($resp);

            if ($stdResp->cStat == 101 || $stdResp->cStat == 128 || $stdResp->cStat == 155) {
                $evento = $nota->eventos()->create([
                    'tipo' => 'cancelamento',
                    'sequencia' => $nota->eventos()->where('tipo', 'cancelamento')->count() + 1,
                    'protocolo' => $stdResp->retEvento->infEvento->nProt ?? '',
                    'justificativa' => $this->justificativa,
                    'xml_envio' => is_string($resp) ? $resp : '',
                    'xml_retorno' => is_string($resp) ? $resp : '',
                    'status' => 'processado',
                ]);

                $nota->update([
                    'status' => 'cancelada',
                    'motivo' => $this->justificativa,
                ]);
            } else {
                $evento = $nota->eventos()->create([
                    'tipo' => 'cancelamento',
                    'sequencia' => $nota->eventos()->where('tipo', 'cancelamento')->count() + 1,
                    'justificativa' => $this->justificativa,
                    'xml_envio' => is_string($resp) ? $resp : '',
                    'xml_retorno' => is_string($resp) ? $resp : '',
                    'status' => 'erro',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao cancelar NF-e {$nota->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function cancelCte(SefazService $sefazService): void
    {
        $cte = Cte::findOrFail($this->documentId);

        if ($cte->status !== 'autorizada') {
            return;
        }

        $company = $cte->company;
        $tools = $sefazService->makeCteTools($company);

        try {
            $resp = $tools->sefazCancela(
                $cte->chave,
                $this->justificativa,
                $cte->protocolo
            );

            $std = new \NFePHP\CTe\Common\Standardize;
            $stdResp = $std->toStd($resp);

            if ($stdResp->cStat == 101 || $stdResp->cStat == 128 || $stdResp->cStat == 155) {
                $cte->eventos()->create([
                    'tipo' => 'cancelamento',
                    'sequencia' => $cte->eventos()->where('tipo', 'cancelamento')->count() + 1,
                    'protocolo' => $stdResp->retEvento->infEvento->nProt ?? '',
                    'justificativa' => $this->justificativa,
                    'xml_envio' => is_string($resp) ? $resp : '',
                    'xml_retorno' => is_string($resp) ? $resp : '',
                    'status' => 'processado',
                ]);

                $cte->update([
                    'status' => 'cancelada',
                    'motivo' => $this->justificativa,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao cancelar CT-e {$cte->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function cancelMdfe(SefazService $sefazService): void
    {
        $mdfe = Mdfe::findOrFail($this->documentId);

        if ($mdfe->status !== 'autorizada') {
            return;
        }

        $company = $mdfe->company;
        $tools = $sefazService->makeMdfeTools($company);

        try {
            $resp = $tools->sefazCancela(
                $mdfe->chave,
                $this->justificativa,
                $mdfe->protocolo
            );

            $std = new \NFePHP\MDFe\Common\Standardize;
            $stdResp = $std->toStd($resp);

            if ($stdResp->cStat == 101 || $stdResp->cStat == 128 || $stdResp->cStat == 155) {
                $mdfe->eventos()->create([
                    'tipo' => 'cancelamento',
                    'sequencia' => $mdfe->eventos()->where('tipo', 'cancelamento')->count() + 1,
                    'protocolo' => $stdResp->retEvento->infEvento->nProt ?? '',
                    'justificativa' => $this->justificativa,
                    'xml_envio' => is_string($resp) ? $resp : '',
                    'xml_retorno' => is_string($resp) ? $resp : '',
                    'status' => 'processado',
                ]);

                $mdfe->update([
                    'status' => 'cancelada',
                    'motivo' => $this->justificativa,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao cancelar MDF-e {$mdfe->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
