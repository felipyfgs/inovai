<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CteNfeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cte_id' => $this->cte_id,
            'chave_nfe' => $this->chave_nfe,
        ];
    }
}
