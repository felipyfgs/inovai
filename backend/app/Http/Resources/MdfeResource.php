<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MdfeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'veiculo_id' => $this->veiculo_id,
            'motorista_id' => $this->motorista_id,
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numero' => $this->numero,
            'chave' => $this->chave,
            'modal' => $this->modal,
            'data_emissao' => $this->data_emissao?->format('Y-m-d'),
            'status' => $this->status,
            'ambiente' => $this->ambiente,
            'uf_carregamento' => $this->uf_carregamento,
            'uf_descarregamento' => $this->uf_descarregamento,
            'veiculo_placa' => $this->veiculo_placa,
            'motorista_cpf' => $this->motorista_cpf,
            'motorista_nome' => $this->motorista_nome,
            'valor_carga' => $this->valor_carga,
            'peso_bruto' => $this->peso_bruto,
            'uf_percurso' => $this->uf_percurso,
            'protocolo' => $this->protocolo,
            'motivo' => $this->motivo,
            'informacoes_adicionais' => $this->informacoes_adicionais,
            'veiculo' => $this->whenLoaded('veiculo'),
            'motorista' => $this->whenLoaded('motorista'),
            'documentos' => $this->whenLoaded('documentos', fn () => MdfeDocumentoResource::collection($this->documentos)),
            'eventos_count' => $this->whenLoaded('eventos', fn () => $this->eventos->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
