<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MdfeDocumentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mdfe_id' => $this->mdfe_id,
            'tipo' => $this->tipo,
            'chave' => $this->chave,
        ];
    }
}
