<?php

namespace App\Jobs;

use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\Nfe;
use App\Services\CteXmlService;
use App\Services\FiscalNumberService;
use App\Services\MdfeXmlService;
use App\Services\NfeXmlService;
use App\Services\SefazService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SignDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public string $tipo,
        public int $documentId
    ) {}

    public function handle(
        SefazService $sefazService,
        NfeXmlService $nfeXmlService,
        CteXmlService $cteXmlService,
        MdfeXmlService $mdfeXmlService,
        FiscalNumberService $fiscalNumberService,
    ): void {
        match ($this->tipo) {
            'nfe' => $this->signNfe($sefazService, $nfeXmlService, $fiscalNumberService),
            'nfce' => $this->signNfe($sefazService, $nfeXmlService, $fiscalNumberService),
            'cte' => $this->signCte($sefazService, $cteXmlService, $fiscalNumberService),
            'mdfe' => $this->signMdfe($sefazService, $mdfeXmlService, $fiscalNumberService),
            default => throw new \InvalidArgumentException("Tipo de documento invalido: {$this->tipo}"),
        };
    }

    private function signNfe(SefazService $sefazService, NfeXmlService $nfeXmlService, FiscalNumberService $fiscalNumberService): void
    {
        $nota = Nfe::findOrFail($this->documentId);

        if (! in_array($nota->status, ['rascunho', 'rejeitada'])) {
            return;
        }

        $xml = $nfeXmlService->make($nota);
        $company = $nota->company;
        $tools = $sefazService->makeNfeTools($company);

        try {
            $xmlAssinado = $tools->signNFe($xml);
            $nota->update([
                'status' => 'assinada',
                'xml_envio' => $xmlAssinado,
            ]);
        } catch (\Throwable $e) {
            Log::error("Erro ao assinar NF-e {$nota->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function signCte(SefazService $sefazService, CteXmlService $cteXmlService, FiscalNumberService $fiscalNumberService): void
    {
        $cte = Cte::findOrFail($this->documentId);

        if (! in_array($cte->status, ['rascunho', 'rejeitada'])) {
            return;
        }

        $xml = $cteXmlService->make($cte);
        $company = $cte->company;
        $tools = $sefazService->makeCteTools($company);

        try {
            $xmlAssinado = $tools->signCTe($xml);
            $cte->update([
                'status' => 'assinada',
                'xml_envio' => $xmlAssinado,
            ]);
        } catch (\Throwable $e) {
            Log::error("Erro ao assinar CT-e {$cte->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function signMdfe(SefazService $sefazService, MdfeXmlService $mdfeXmlService, FiscalNumberService $fiscalNumberService): void
    {
        $mdfe = Mdfe::findOrFail($this->documentId);

        if (! in_array($mdfe->status, ['rascunho', 'rejeitada'])) {
            return;
        }

        $xml = $mdfeXmlService->make($mdfe);
        $company = $mdfe->company;
        $tools = $sefazService->makeMdfeTools($company);

        try {
            $xmlAssinado = $tools->signMDFe($xml);
            $mdfe->update([
                'status' => 'assinada',
                'xml_envio' => $xmlAssinado,
            ]);
        } catch (\Throwable $e) {
            Log::error("Erro ao assinar MDF-e {$mdfe->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
