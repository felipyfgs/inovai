<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NfeItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nfe_id' => $this->nfe_id,
            'produto_id' => $this->produto_id,
            'numero_item' => $this->numero_item,
            'codigo' => $this->codigo,
            'descricao' => $this->descricao,
            'ncm' => $this->ncm,
            'cest' => $this->cest,
            'cfop' => $this->cfop,
            'unidade' => $this->unidade,
            'quantidade' => $this->quantidade,
            'valor_unitario' => $this->valor_unitario,
            'valor_total' => $this->valor_total,
            'valor_desconto' => $this->valor_desconto,
            'valor_frete' => $this->valor_frete,
            'valor_seguro' => $this->valor_seguro,
            'valor_outras' => $this->valor_outras,
            'origem' => $this->origem,
            'cst_icms' => $this->cst_icms,
            'csosn' => $this->csosn,
            'bc_icms' => $this->bc_icms,
            'aliq_icms' => $this->aliq_icms,
            'valor_icms' => $this->valor_icms,
            'bc_icms_st' => $this->bc_icms_st,
            'aliq_icms_st' => $this->aliq_icms_st,
            'valor_icms_st' => $this->valor_icms_st,
            'cst_ipi' => $this->cst_ipi,
            'bc_ipi' => $this->bc_ipi,
            'aliq_ipi' => $this->aliq_ipi,
            'valor_ipi' => $this->valor_ipi,
            'cst_pis' => $this->cst_pis,
            'bc_pis' => $this->bc_pis,
            'aliq_pis' => $this->aliq_pis,
            'valor_pis' => $this->valor_pis,
            'cst_cofins' => $this->cst_cofins,
            'bc_cofins' => $this->bc_cofins,
            'aliq_cofins' => $this->aliq_cofins,
            'valor_cofins' => $this->valor_cofins,
            'produto' => $this->whenLoaded('produto'),
        ];
    }
}
