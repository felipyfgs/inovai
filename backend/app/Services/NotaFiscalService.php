<?php

namespace App\Services;

use App\Models\NotaFiscal;
use App\Models\NotaFiscalEvento;
use App\Models\NotaFiscalItem;
use Illuminate\Support\Facades\DB;

class NotaFiscalService
{
    public function create(array $data, $company): NotaFiscal
    {
        return DB::transaction(function () use ($data, $company) {
            $nota = NotaFiscal::create([
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

            $this->saveItens($nota, $data['itens'] ?? []);
            $this->calcularTotais($nota);

            if (isset($data['numero'])) {
                $nextNumero = (int) $data['numero'] + 1;
                $company->update(['proximo_numero_nfe' => $nextNumero]);
            }

            return $nota->load('itens', 'pessoa', 'transportadora');
        });
    }

    public function update(NotaFiscal $nota, array $data): NotaFiscal
    {
        return DB::transaction(function () use ($nota, $data) {
            $nota->update(collect($data)->except(['itens'])->toArray());

            if (isset($data['itens'])) {
                $nota->itens()->delete();
                $this->saveItens($nota, $data['itens']);
                $this->calcularTotais($nota);
            }

            return $nota->fresh('itens', 'pessoa', 'transportadora');
        });
    }

    public function saveItens(NotaFiscal $nota, array $itens): void
    {
        foreach ($itens as $index => $item) {
            NotaFiscalItem::create([
                'nota_fiscal_id' => $nota->id,
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

    public function calcularTotais(NotaFiscal $nota): void
    {
        $nota->load('itens');

        $valorProdutos = $nota->itens->sum('valor_total');
        $valorFrete = $nota->itens->sum('valor_frete');
        $valorSeguro = $nota->itens->sum('valor_seguro');
        $valorDesconto = $nota->itens->sum('valor_desconto');
        $valorOutras = $nota->itens->sum('valor_outras');
        $valorIcms = $nota->itens->sum('valor_icms');
        $valorIcmsSt = $nota->itens->sum('valor_icms_st');
        $valorIpi = $nota->itens->sum('valor_ipi');
        $valorPis = $nota->itens->sum('valor_pis');
        $valorCofins = $nota->itens->sum('valor_cofins');

        $valorTotal = $valorProdutos + $valorFrete + $valorSeguro - $valorDesconto + $valorOutras + $valorIpi;

        $nota->update([
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

    public function createEvento(NotaFiscal $nota, string $tipo, array $dados): NotaFiscalEvento
    {
        $sequencia = $nota->eventos()->where('tipo', $tipo)->count() + 1;

        return $nota->eventos()->create([
            'tipo' => $tipo,
            'sequencia' => $sequencia,
            'justificativa' => $dados['justificativa'] ?? null,
            'correcao' => $dados['correcao'] ?? null,
            'status' => 'pendente',
        ]);
    }
}
