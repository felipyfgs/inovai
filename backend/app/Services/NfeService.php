<?php

namespace App\Services;

use App\Models\Nfe;
use App\Models\NfeEvento;
use App\Models\NfeItem;
use Illuminate\Support\Facades\DB;

class NfeService
{
    public function create(array $data, $company): Nfe
    {
        return DB::transaction(function () use ($data, $company) {
            $nfe = Nfe::create([
                'company_id' => $company->id,
                'pessoa_id' => $data['pessoa_id'] ?? null,
                'pedido_id' => $data['pedido_id'] ?? null,
                'transportadora_id' => $data['transportadora_id'] ?? null,
                'modelo' => $data['modelo'] ?? 55,
                'serie' => $data['serie'] ?? ($company->serie_nfe ?? 1),
                'numero' => $data['numero'] ?? ($company->proximo_numero_nfe ?? 1),
                'natureza_operacao' => $data['natureza_operacao'] ?? 'Venda de Mercadoria',
                'tipo_operacao' => $data['tipo_operacao'] ?? 1,
                'finalidade' => $data['finalidade'] ?? 1,
                'data_emissao' => $data['data_emissao'] ?? now()->toDateString(),
                'data_saida' => $data['data_saida'] ?? null,
                'status' => 'rascunho',
                'ambiente' => $company->ambiente === 'producao' ? 1 : 2,
                'frete_por' => $data['frete_por'] ?? 9,
                'informacoes_adicionais' => $data['informacoes_adicionais'] ?? null,
                'informacoes_fisco' => $data['informacoes_fisco'] ?? null,
            ]);

            $this->saveItens($nfe, $data['itens'] ?? []);
            $this->calcularTotais($nfe);

            if (isset($data['numero'])) {
                $nextNumero = (int) $data['numero'] + 1;
                $company->update(['proximo_numero_nfe' => $nextNumero]);
            }

            return $nfe->load('itens', 'pessoa', 'transportadora');
        });
    }

    public function update(Nfe $nfe, array $data): Nfe
    {
        return DB::transaction(function () use ($nfe, $data) {
            $nfe->update(collect($data)->except(['itens'])->toArray());

            if (isset($data['itens'])) {
                $nfe->itens()->delete();
                $this->saveItens($nfe, $data['itens']);
                $this->calcularTotais($nfe);
            }

            return $nfe->fresh('itens', 'pessoa', 'transportadora');
        });
    }

    public function saveItens(Nfe $nfe, array $itens): void
    {
        foreach ($itens as $index => $item) {
            NfeItem::create([
                'nfe_id' => $nfe->id,
                'produto_id' => $item['produto_id'] ?? null,
                'numero_item' => $item['numero_item'] ?? ($index + 1),
                'codigo' => $item['codigo'] ?? null,
                'descricao' => $item['descricao'],
                'ncm' => $item['ncm'] ?? null,
                'cest' => $item['cest'] ?? null,
                'cfop' => $item['cfop'],
                'unidade' => $item['unidade'] ?? 'UN',
                'quantidade' => $item['quantidade'],
                'valor_unitario' => $item['valor_unitario'],
                'valor_total' => $item['valor_total'],
                'valor_desconto' => $item['valor_desconto'] ?? 0,
                'valor_frete' => $item['valor_frete'] ?? 0,
                'valor_seguro' => $item['valor_seguro'] ?? 0,
                'valor_outras' => $item['valor_outras'] ?? 0,
                'origem' => $item['origem'] ?? 0,
                'cst_icms' => $item['cst_icms'] ?? null,
                'csosn' => $item['csosn'] ?? null,
                'bc_icms' => $item['bc_icms'] ?? 0,
                'aliq_icms' => $item['aliq_icms'] ?? 0,
                'valor_icms' => $item['valor_icms'] ?? 0,
                'bc_icms_st' => $item['bc_icms_st'] ?? 0,
                'aliq_icms_st' => $item['aliq_icms_st'] ?? 0,
                'valor_icms_st' => $item['valor_icms_st'] ?? 0,
                'cst_ipi' => $item['cst_ipi'] ?? null,
                'bc_ipi' => $item['bc_ipi'] ?? 0,
                'aliq_ipi' => $item['aliq_ipi'] ?? 0,
                'valor_ipi' => $item['valor_ipi'] ?? 0,
                'cst_pis' => $item['cst_pis'] ?? null,
                'bc_pis' => $item['bc_pis'] ?? 0,
                'aliq_pis' => $item['aliq_pis'] ?? 0,
                'valor_pis' => $item['valor_pis'] ?? 0,
                'cst_cofins' => $item['cst_cofins'] ?? null,
                'bc_cofins' => $item['bc_cofins'] ?? 0,
                'aliq_cofins' => $item['aliq_cofins'] ?? 0,
                'valor_cofins' => $item['valor_cofins'] ?? 0,
            ]);
        }
    }

    public function calcularTotais(Nfe $nfe): void
    {
        $nfe->load('itens');

        $valorProdutos = $nfe->itens->sum('valor_total');
        $valorFrete = $nfe->itens->sum('valor_frete');
        $valorSeguro = $nfe->itens->sum('valor_seguro');
        $valorDesconto = $nfe->itens->sum('valor_desconto');
        $valorOutras = $nfe->itens->sum('valor_outras');
        $valorIcms = $nfe->itens->sum('valor_icms');
        $valorIcmsSt = $nfe->itens->sum('valor_icms_st');
        $valorIpi = $nfe->itens->sum('valor_ipi');
        $valorPis = $nfe->itens->sum('valor_pis');
        $valorCofins = $nfe->itens->sum('valor_cofins');

        $valorTotal = $valorProdutos + $valorFrete + $valorSeguro - $valorDesconto + $valorOutras + $valorIpi;

        $nfe->update([
            'valor_produtos' => $valorProdutos,
            'valor_frete' => $valorFrete,
            'valor_seguro' => $valorSeguro,
            'valor_desconto' => $valorDesconto,
            'valor_outras' => $valorOutras,
            'valor_icms' => $valorIcms,
            'valor_icms_st' => $valorIcmsSt,
            'valor_ipi' => $valorIpi,
            'valor_pis' => $valorPis,
            'valor_cofins' => $valorCofins,
            'valor_total' => $valorTotal,
        ]);
    }

    public function createEvento(Nfe $nfe, string $tipo, array $dados): NfeEvento
    {
        $sequencia = $nfe->eventos()->where('tipo', $tipo)->count() + 1;

        return $nfe->eventos()->create([
            'tipo' => $tipo,
            'sequencia' => $sequencia,
            'justificativa' => $dados['justificativa'] ?? null,
            'correcao' => $dados['correcao'] ?? null,
            'status' => 'pendente',
        ]);
    }
}
