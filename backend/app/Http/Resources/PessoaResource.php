<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PessoaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'tipo_pessoa' => $this->tipo_pessoa,
            'razao_social' => $this->razao_social,
            'fantasia' => $this->fantasia,
            'cpf_cnpj' => $this->cpf_cnpj,
            'ie' => $this->ie,
            'telefone' => $this->telefone,
            'celular' => $this->celular,
            'email' => $this->email,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'municipio' => $this->municipio,
            'uf' => $this->uf,
            'cep' => $this->cep,
            'is_active' => $this->is_active,
        ];
    }
}
