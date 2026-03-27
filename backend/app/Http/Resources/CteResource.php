<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'remetente_id' => $this->remetente_id,
            'destinatario_id' => $this->destinatario_id,
            'expedidor_id' => $this->expedidor_id,
            'recebedor_id' => $this->recebedor_id,
            'tomador_id' => $this->tomador_id,
            'tomador_tipo' => $this->tomador_tipo,
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'chave' => $this->chave,
            'cfop' => $this->cfop,
            'natureza_operacao' => $this->natureza_operacao,
            'modal' => $this->modal,
            'data_emissao' => $this->data_emissao?->format('Y-m-d'),
            'status' => $this->status,
            'ambiente' => $this->ambiente,
            'valor_servico' => $this->valor_servico,
            'valor_receber' => $this->valor_receber,
            'valor_icms' => $this->valor_icms,
            'valor_total' => $this->valor_total,
            'uf_inicio' => $this->uf_inicio,
            'uf_fim' => $this->uf_fim,
            'protocolo' => $this->protocolo,
            'motivo' => $this->motivo,
            'informacoes_adicionais' => $this->informacoes_adicionais,
            'remetente' => $this->whenLoaded('remetente'),
            'destinatario' => $this->whenLoaded('destinatario'),
            'expedidor' => $this->whenLoaded('expedidor'),
            'recebedor' => $this->whenLoaded('recebedor'),
            'tomador' => $this->whenLoaded('tomador'),
            'nfes' => $this->whenLoaded('nfes', fn () => CteNfeResource::collection($this->nfes)),
            'eventos_count' => $this->whenLoaded('eventos', fn () => $this->eventos->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
