<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produto_id' => $this->produto_id,
            'descricao' => $this->descricao,
            'quantidade' => $this->quantidade,
            'valor_unitario' => $this->valor_unitario,
            'desconto' => $this->desconto,
            'valor_total' => $this->valor_total,
            'produto' => new ProdutoResource($this->whenLoaded('produto')),
        ];
    }
}
