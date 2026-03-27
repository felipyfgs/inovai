<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MdfeEventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mdfe_id' => $this->mdfe_id,
            'tipo' => $this->tipo,
            'sequencia' => $this->sequencia,
            'protocolo' => $this->protocolo,
            'justificativa' => $this->justificativa,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
