<?php

namespace App\Jobs;

use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\Nfe;
use App\Services\FiscalNumberService;
use App\Services\SefazService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;

class TransmitDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = [30, 60, 120];

    public function __construct(
        public string $tipo,
        public int $documentId
    ) {}

    public function handle(
        SefazService $sefazService,
        FiscalNumberService $fiscalNumberService,
    ): void {
        match ($this->tipo) {
            'nfe', 'nfce' => $this->transmitNfe($sefazService, $fiscalNumberService),
            'cte' => $this->transmitCte($sefazService),
            'mdfe' => $this->transmitMdfe($sefazService),
            default => throw new \InvalidArgumentException("Tipo de documento invalido: {$this->tipo}"),
        };
    }

    private function transmitNfe(SefazService $sefazService, FiscalNumberService $fiscalNumberService): void
    {
        $nota = Nfe::findOrFail($this->documentId);

        if ($nota->status !== 'assinada') {
            return;
        }

        $company = $nota->company;
        $tools = $sefazService->makeNfeTools($company);

        if ($nota->isNfce()) {
            $tools->model(65);
        }

        try {
            $nota->update(['status' => 'transmitida']);
            $idLote = $fiscalNumberService->gerarIdLote();
            $resp = $tools->sefazEnviaLote([$nota->xml_envio], $idLote);

            $std = new Standardize;
            $stdResp = $std->toStd($resp);

            if ($stdResp->cStat != 103) {
                $nota->update([
                    'status' => 'rejeitada',
                    'xml_retorno' => $resp,
                    'motivo' => "[{$stdResp->cStat}] {$stdResp->xMotivo}",
                ]);

                return;
            }

            $recibo = $stdResp->infRec->nRec;
            $nota->update(['recibo' => $recibo]);

            $protocolo = $tools->sefazConsultaRecibo($recibo);
            $stdProt = $std->toStd($protocolo);

            if ($stdProt->cStat == 100 || $stdProt->cStat == 104) {
                $xmlProtocolado = Complements::toAuthorize($nota->xml_envio, $protocolo);
                $protocoloNum = $stdProt->protNFe->nProt;

                $nota->update([
                    'status' => 'autorizada',
                    'xml_autorizado' => $xmlProtocolado,
                    'xml_retorno' => $protocolo,
                    'protocolo' => $protocoloNum,
                ]);
            } else {
                $motivo = $stdProt->xMotivo ?? 'Erro desconhecido';
                $nota->update([
                    'status' => 'rejeitada',
                    'xml_retorno' => $protocolo,
                    'motivo' => "[{$stdProt->cStat}] {$motivo}",
                ]);
            }
        } catch (\Throwable $e) {
            $nota->update([
                'status' => 'rejeitada',
                'motivo' => 'Erro na transmissao: '.$e->getMessage(),
            ]);
            Log::error("Erro ao transmitir NF-e {$nota->id}: {$e->getMessage()}");
        }
    }

    private function transmitCte(SefazService $sefazService): void
    {
        $cte = Cte::findOrFail($this->documentId);

        if ($cte->status !== 'assinada') {
            return;
        }

        $company = $cte->company;
        $tools = $sefazService->makeCteTools($company);

        try {
            $cte->update(['status' => 'transmitida']);
            $resp = $tools->sefazEnviaCTe($cte->xml_envio);

            $std = new \NFePHP\CTe\Common\Standardize;
            $stdResp = $std->toStd($resp);

            if (! isset($stdResp->cStat) || $stdResp->cStat != 104) {
                $cStat = $stdResp->cStat ?? '0';
                $xMotivo = $stdResp->xMotivo ?? 'Erro desconhecido';
                $cte->update([
                    'status' => 'rejeitada',
                    'xml_retorno' => $resp,
                    'motivo' => "[{$cStat}] {$xMotivo}",
                ]);

                return;
            }

            if ($stdResp->cStat == 100 || $stdResp->cStat == 104) {
                $xmlProtocolado = \NFePHP\CTe\Complements::toAuthorize($cte->xml_envio, $resp);
                $protocoloNum = $stdResp->protCTe->nProt ?? '';

                $cte->update([
                    'status' => 'autorizada',
                    'xml_autorizado' => $xmlProtocolado,
                    'xml_retorno' => $resp,
                    'protocolo' => $protocoloNum,
                ]);
            }
        } catch (\Throwable $e) {
            $cte->update([
                'status' => 'rejeitada',
                'motivo' => 'Erro na transmissao: '.$e->getMessage(),
            ]);
            Log::error("Erro ao transmitir CT-e {$cte->id}: {$e->getMessage()}");
        }
    }

    private function transmitMdfe(SefazService $sefazService): void
    {
        $mdfe = Mdfe::findOrFail($this->documentId);

        if ($mdfe->status !== 'assinada') {
            return;
        }

        $company = $mdfe->company;
        $tools = $sefazService->makeMdfeTools($company);

        try {
            $mdfe->update(['status' => 'transmitida']);
            $resp = $tools->sefazEnviaLote($mdfe->xml_envio);

            $std = new \NFePHP\MDFe\Common\Standardize;
            $stdResp = $std->toStd($resp);

            if (isset($stdResp->cStat) && $stdResp->cStat == 100) {
                $xmlProtocolado = \NFePHP\MDFe\Complements::toAuthorize($mdfe->xml_envio, $resp);
                $protocoloNum = $stdResp->protMDFe->nProt ?? '';

                $mdfe->update([
                    'status' => 'autorizada',
                    'xml_autorizado' => $xmlProtocolado,
                    'xml_retorno' => $resp,
                    'protocolo' => $protocoloNum,
                ]);
            } else {
                $motivo = $stdResp->xMotivo ?? 'Erro desconhecido';
                $mdfe->update([
                    'status' => 'rejeitada',
                    'xml_retorno' => $resp,
                    'motivo' => "[{$stdResp->cStat}] {$motivo}",
                ]);
            }
        } catch (\Throwable $e) {
            $mdfe->update([
                'status' => 'rejeitada',
                'motivo' => 'Erro na transmissao: '.$e->getMessage(),
            ]);
            Log::error("Erro ao transmitir MDF-e {$mdfe->id}: {$e->getMessage()}");
        }
    }
}
