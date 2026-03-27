<?php

namespace App\Services;

use App\Models\Cte;
use App\Models\CteNfe;
use Illuminate\Support\Facades\DB;

class CteService
{
    public function create(array $data, $company): Cte
    {
        return DB::transaction(function () use ($data, $company) {
            $cte = Cte::create([
                'company_id' => $company->id,
                'remetente_id' => $data['remetente_id'] ?? null,
                'destinatario_id' => $data['destinatario_id'] ?? null,
                'expedidor_id' => $data['expedidor_id'] ?? null,
                'recebedor_id' => $data['recebedor_id'] ?? null,
                'tomador_id' => $data['tomador_id'] ?? null,
                'tomador_tipo' => $data['tomador_tipo'] ?? 0,
                'modelo' => 57,
                'serie' => $data['serie'] ?? ($company->serie_cte ?? 1),
                'numero' => $data['numero'] ?? ($company->proximo_numero_cte ?? 1),
                'cfop' => $data['cfop'] ?? null,
                'natureza_operacao' => $data['natureza_operacao'] ?? 'Prestacao de Servico de Transporte',
                'modal' => $data['modal'] ?? 1,
                'data_emissao' => $data['data_emissao'] ?? now()->toDateString(),
                'status' => 'rascunho',
                'ambiente' => $company->ambiente === 'producao' ? 1 : 2,
                'valor_servico' => $data['valor_servico'] ?? 0,
                'valor_receber' => $data['valor_receber'] ?? 0,
                'valor_icms' => $data['valor_icms'] ?? 0,
                'valor_total' => $data['valor_total'] ?? 0,
                'uf_inicio' => $data['uf_inicio'] ?? null,
                'municipio_inicio' => $data['municipio_inicio'] ?? null,
                'municipio_inicio_ibge' => $data['municipio_inicio_ibge'] ?? null,
                'uf_fim' => $data['uf_fim'] ?? null,
                'municipio_fim' => $data['municipio_fim'] ?? null,
                'municipio_fim_ibge' => $data['municipio_fim_ibge'] ?? null,
                'informacoes_adicionais' => $data['informacoes_adicionais'] ?? null,
            ]);

            foreach ($data['chaves_nfe'] ?? [] as $chave) {
                CteNfe::create([
                    'cte_id' => $cte->id,
                    'chave_nfe' => $chave,
                ]);
            }

            if (isset($data['numero'])) {
                $nextNumero = (int) $data['numero'] + 1;
                $company->update(['proximo_numero_cte' => $nextNumero]);
            }

            return $cte->load('nfes', 'remetente', 'destinatario', 'expedidor', 'recebedor', 'tomador');
        });
    }

    public function update(Cte $cte, array $data): Cte
    {
        return DB::transaction(function () use ($cte, $data) {
            $cte->update(collect($data)->except(['chaves_nfe'])->toArray());

            if (isset($data['chaves_nfe'])) {
                $cte->nfes()->delete();
                foreach ($data['chaves_nfe'] as $chave) {
                    CteNfe::create([
                        'cte_id' => $cte->id,
                        'chave_nfe' => $chave,
                    ]);
                }
            }

            return $cte->fresh('nfes', 'remetente', 'destinatario', 'expedidor', 'recebedor', 'tomador');
        });
    }
}
