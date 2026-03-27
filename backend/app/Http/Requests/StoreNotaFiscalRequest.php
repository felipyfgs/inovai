<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotaFiscalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pessoa_id' => ['required', 'exists:pessoas,id'],
            'pedido_id' => ['nullable', 'exists:pedidos,id'],
            'transportadora_id' => ['nullable', 'exists:transportadoras,id'],
            'modelo' => ['required', 'integer', 'in:55,65'],
            'serie' => ['nullable', 'integer'],
            'numero' => ['nullable', 'integer'],
            'natureza_operacao' => ['nullable', 'string', 'max:60'],
            'tipo_operacao' => ['nullable', 'integer', 'in:0,1'],
            'finalidade' => ['nullable', 'integer', 'in:1,2,3,4'],
            'data_emissao' => ['required', 'date'],
            'data_saida' => ['nullable', 'date'],
            'frete_por' => ['nullable', 'integer', 'in:0,1,2,9'],
            'informacoes_adicionais' => ['nullable', 'string'],
            'informacoes_fisco' => ['nullable', 'string'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.descricao' => ['required', 'string', 'max:120'],
            'itens.*.cfop' => ['required', 'string', 'max:4'],
            'itens.*.unidade' => ['required', 'string', 'max:6'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0.0001'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'itens.*.valor_total' => ['required', 'numeric', 'min:0'],
            'itens.*.ncm' => ['nullable', 'string', 'max:8'],
            'itens.*.cest' => ['nullable', 'string', 'max:7'],
            'itens.*.cst_icms' => ['nullable', 'string', 'max:3'],
            'itens.*.csosn' => ['nullable', 'string', 'max:4'],
            'itens.*.origem' => ['nullable', 'integer'],
            'itens.*.bc_icms' => ['nullable', 'numeric', 'min:0'],
            'itens.*.aliq_icms' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'itens.*.valor_icms' => ['nullable', 'numeric', 'min:0'],
            'itens.*.cst_ipi' => ['nullable', 'string', 'max:2'],
            'itens.*.bc_ipi' => ['nullable', 'numeric', 'min:0'],
            'itens.*.aliq_ipi' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'itens.*.valor_ipi' => ['nullable', 'numeric', 'min:0'],
            'itens.*.cst_pis' => ['nullable', 'string', 'max:2'],
            'itens.*.bc_pis' => ['nullable', 'numeric', 'min:0'],
            'itens.*.aliq_pis' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'itens.*.valor_pis' => ['nullable', 'numeric', 'min:0'],
            'itens.*.cst_cofins' => ['nullable', 'string', 'max:2'],
            'itens.*.bc_cofins' => ['nullable', 'numeric', 'min:0'],
            'itens.*.aliq_cofins' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'itens.*.valor_cofins' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
