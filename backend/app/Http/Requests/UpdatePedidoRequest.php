<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pessoa_id' => ['nullable', 'exists:pessoas,id'],
            'data' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:pendente,confirmado,faturado,cancelado'],
            'observacoes' => ['nullable', 'string'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'itens' => ['sometimes', 'array', 'min:1'],
            'itens.*.id' => ['nullable', 'integer'],
            'itens.*.produto_id' => ['nullable', 'exists:produtos,id'],
            'itens.*.descricao' => ['required', 'string', 'max:255'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0.0001'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'itens.*.desconto' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
