<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'codigo_barras' => $this->codigo_barras,
            'descricao' => $this->descricao,
            'ncm' => $this->ncm,
            'unidade' => $this->unidade,
            'preco_venda' => $this->preco_venda,
            'preco_custo' => $this->preco_custo,
            'estoque_minimo' => $this->estoque_minimo,
            'is_active' => $this->is_active,
        ];
    }
}
