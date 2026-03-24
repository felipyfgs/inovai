<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrcamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'data' => $this->data,
            'validade' => $this->validade,
            'status' => $this->status,
            'observacoes' => $this->observacoes,
            'desconto' => $this->desconto,
            'valor_total' => $this->valor_total,
            'pessoa' => new PessoaResource($this->whenLoaded('pessoa')),
            'itens' => OrcamentoItemResource::collection($this->whenLoaded('itens')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
