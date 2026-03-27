<?php

namespace App\Jobs;

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

class EventDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = [30, 60, 120];

    public function __construct(
        public string $tipo,
        public int $documentId,
        public string $eventoTipo,
        public array $dados
    ) {}

    public function handle(SefazService $sefazService): void
    {
        match ($this->tipo) {
            'nfe' => $this->eventNfe($sefazService),
            'mdfe' => $this->eventMdfe($sefazService),
            default => throw new \InvalidArgumentException("Tipo nao suportado para eventos: {$this->tipo}"),
        };
    }

    private function eventNfe(SefazService $sefazService): void
    {
        $nota = Nfe::findOrFail($this->documentId);

        if ($nota->status !== 'autorizada') {
            return;
        }

        $company = $nota->company;
        $tools = $sefazService->makeNfeTools($company);

        try {
            if ($this->eventoTipo === 'cce') {
                $sequencia = $nota->eventos()->where('tipo', 'cce')->count() + 1;
                $correcao = $this->dados['correcao'] ?? [];
                $grupoCorrecao = '';

                foreach ($correcao as $item) {
                    $grupoCorrecao .= "<campoCorrecao><grupoCorrecao>{$item['grupo']}</grupoCorrecao>"
                        ."<campo>{$item['campo']}</campo>"
                        ."<valorAnterior>{$item['valor_anterior']}</valorAnterior>"
                        ."<valorNovo>{$item['valor_novo']}</valorNovo>"
                        .'</campoCorrecao>';
                }

                $resp = $tools->sefazCCe(
                    $nota->chave,
                    $grupoCorrecao,
                    $sequencia
                );

                $std = new Standardize;
                $stdResp = $std->toStd($resp);

                $evento = $nota->eventos()->create([
                    'tipo' => 'cce',
                    'sequencia' => $sequencia,
                    'protocolo' => $stdResp->retEvento->infEvento->nProt ?? '',
                    'correcao' => json_encode($correcao),
                    'xml_envio' => is_string($resp) ? $resp : '',
                    'xml_retorno' => is_string($resp) ? $resp : '',
                    'status' => ($stdResp->cStat == 135) ? 'processado' : 'erro',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao enviar evento NF-e {$nota->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function eventMdfe(SefazService $sefazService): void
    {
        $mdfe = Mdfe::findOrFail($this->documentId);

        if ($mdfe->status !== 'autorizada') {
            return;
        }

        $company = $mdfe->company;
        $tools = $sefazService->makeMdfeTools($company);

        try {
            if ($this->eventoTipo === 'encerramento') {
                $uf = $this->dados['uf'] ?? '';
                $municipio = $this->dados['municipio'] ?? '';
                $resp = $tools->sefazEncerra(
                    $mdfe->chave,
                    $mdfe->protocolo,
                    date('Y-m-d\TH:i:sP'),
                    $uf,
                    (int) $this->dados['municipio_ibge'] ?? 0,
                    $municipio
                );

                $std = new \NFePHP\MDFe\Common\Standardize;
                $stdResp = $std->toStd($resp);

                $mdfe->eventos()->create([
                    'tipo' => 'encerramento',
                    'sequencia' => 1,
                    'protocolo' => $stdResp->retEvento->infEvento->nProt ?? '',
                    'justificativa' => "Encerramento em {$uf}/{$municipio}",
                    'xml_envio' => is_string($resp) ? $resp : '',
                    'xml_retorno' => is_string($resp) ? $resp : '',
                    'status' => 'processado',
                ]);

                if ($stdResp->cStat == 135) {
                    $mdfe->update(['status' => 'cancelada']);
                }
            } elseif ($this->eventoTipo === 'inclusao_condutor') {
                $condutor = $this->dados['condutor'] ?? [];
                $resp = $tools->sefazIncluiCondutor(
                    $mdfe->chave,
                    $mdfe->protocolo,
                    $condutor['cpf'] ?? '',
                    $condutor['nome'] ?? ''
                );

                $std = new \NFePHP\MDFe\Common\Standardize;
                $stdResp = $std->toStd($resp);

                $mdfe->eventos()->create([
                    'tipo' => 'inclusao_condutor',
                    'sequencia' => $mdfe->eventos()->where('tipo', 'inclusao_condutor')->count() + 1,
                    'protocolo' => $stdResp->retEvento->infEvento->nProt ?? '',
                    'xml_envio' => is_string($resp) ? $resp : '',
                    'xml_retorno' => is_string($resp) ? $resp : '',
                    'status' => 'processado',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao enviar evento MDF-e {$mdfe->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
