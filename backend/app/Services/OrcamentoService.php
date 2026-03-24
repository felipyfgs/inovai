<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;

class OrcamentoService
{
    public function createOrcamento(array $data, $company): Orcamento
    {
        return DB::transaction(function () use ($data, $company) {
            $lastNumero = $company->orcamentos()->max('numero') ?? 0;

            $orcamento = Orcamento::create([
                'company_id' => $company->id,
                'pessoa_id' => $data['pessoa_id'] ?? null,
                'numero' => $lastNumero + 1,
                'data' => $data['data'],
                'validade' => $data['validade'] ?? null,
                'observacoes' => $data['observacoes'] ?? null,
                'desconto' => $data['desconto'] ?? 0,
                'status' => 'rascunho',
            ]);

            $total = 0;
            foreach ($data['itens'] as $item) {
                $itemTotal = ($item['quantidade'] * $item['valor_unitario']) - ($item['desconto'] ?? 0);
                OrcamentoItem::create([
                    'orcamento_id' => $orcamento->id,
                    'produto_id' => $item['produto_id'] ?? null,
                    'descricao' => $item['descricao'],
                    'quantidade' => $item['quantidade'],
                    'valor_unitario' => $item['valor_unitario'],
                    'desconto' => $item['desconto'] ?? 0,
                    'valor_total' => $itemTotal,
                ]);
                $total += $itemTotal;
            }

            $orcamento->update(['valor_total' => $total - ($data['desconto'] ?? 0)]);

            return $orcamento;
        });
    }

    public function updateOrcamento(Orcamento $orcamento, array $data): Orcamento
    {
        return DB::transaction(function () use ($orcamento, $data) {
            $orcamento->update(collect($data)->except('itens')->toArray());

            if (isset($data['itens'])) {
                $orcamento->itens()->delete();

                $total = 0;
                foreach ($data['itens'] as $item) {
                    $itemTotal = ($item['quantidade'] * $item['valor_unitario']) - ($item['desconto'] ?? 0);
                    OrcamentoItem::create([
                        'orcamento_id' => $orcamento->id,
                        'produto_id' => $item['produto_id'] ?? null,
                        'descricao' => $item['descricao'],
                        'quantidade' => $item['quantidade'],
                        'valor_unitario' => $item['valor_unitario'],
                        'desconto' => $item['desconto'] ?? 0,
                        'valor_total' => $itemTotal,
                    ]);
                    $total += $itemTotal;
                }

                $orcamento->update(['valor_total' => $total - ($orcamento->desconto ?? 0)]);
            }

            return $orcamento->fresh();
        });
    }

    public function convertToPedido(Orcamento $orcamento, $company): Pedido
    {
        return DB::transaction(function () use ($orcamento, $company) {
            $orcamento->load('itens');
            $lastNumero = $company->pedidos()->max('numero') ?? 0;

            $pedido = Pedido::create([
                'company_id' => $orcamento->company_id,
                'pessoa_id' => $orcamento->pessoa_id,
                'orcamento_id' => $orcamento->id,
                'numero' => $lastNumero + 1,
                'data' => now()->toDateString(),
                'status' => 'pendente',
                'observacoes' => $orcamento->observacoes,
                'desconto' => $orcamento->desconto,
                'valor_total' => $orcamento->valor_total,
            ]);

            foreach ($orcamento->itens as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $item->produto_id,
                    'descricao' => $item->descricao,
                    'quantidade' => $item->quantidade,
                    'valor_unitario' => $item->valor_unitario,
                    'desconto' => $item->desconto,
                    'valor_total' => $item->valor_total,
                ]);
            }

            $orcamento->update(['status' => 'convertido']);

            return $pedido;
        });
    }
}
