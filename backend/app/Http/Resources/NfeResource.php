<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NfeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'pessoa_id' => $this->pessoa_id,
            'pedido_id' => $this->pedido_id,
            'transportadora_id' => $this->transportadora_id,
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'chave' => $this->chave,
            'natureza_operacao' => $this->natureza_operacao,
            'tipo_operacao' => $this->tipo_operacao,
            'finalidade' => $this->finalidade,
            'data_emissao' => $this->data_emissao?->format('Y-m-d'),
            'data_saida' => $this->data_saida?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'ambiente' => $this->ambiente,
            'valor_produtos' => $this->valor_produtos,
            'valor_frete' => $this->valor_frete,
            'valor_seguro' => $this->valor_seguro,
            'valor_desconto' => $this->valor_desconto,
            'valor_outras' => $this->valor_outras,
            'valor_icms' => $this->valor_icms,
            'valor_icms_st' => $this->valor_icms_st,
            'valor_ipi' => $this->valor_ipi,
            'valor_pis' => $this->valor_pis,
            'valor_cofins' => $this->valor_cofins,
            'valor_total' => $this->valor_total,
            'frete_por' => $this->frete_por,
            'protocolo' => $this->protocolo,
            'recibo' => $this->recibo,
            'motivo' => $this->motivo,
            'informacoes_adicionais' => $this->informacoes_adicionais,
            'pessoa' => $this->whenLoaded('pessoa'),
            'transportadora' => $this->whenLoaded('transportadora'),
            'itens_count' => $this->whenLoaded('itens', fn () => $this->itens->count()),
            'eventos_count' => $this->whenLoaded('eventos', fn () => $this->eventos->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
