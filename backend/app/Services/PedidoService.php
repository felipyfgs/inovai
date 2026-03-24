<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;

class PedidoService
{
    public function createPedido(array $data, $company): Pedido
    {
        return DB::transaction(function () use ($data, $company) {
            $lastNumero = $company->pedidos()->max('numero') ?? 0;

            $pedido = Pedido::create([
                'company_id' => $company->id,
                'pessoa_id' => $data['pessoa_id'] ?? null,
                'orcamento_id' => $data['orcamento_id'] ?? null,
                'numero' => $lastNumero + 1,
                'data' => $data['data'],
                'status' => 'pendente',
                'observacoes' => $data['observacoes'] ?? null,
                'desconto' => $data['desconto'] ?? 0,
                'valor_total' => 0,
            ]);

            $total = 0;
            foreach ($data['itens'] as $item) {
                $itemTotal = ($item['quantidade'] * $item['valor_unitario']) - ($item['desconto'] ?? 0);
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $item['produto_id'] ?? null,
                    'descricao' => $item['descricao'],
                    'quantidade' => $item['quantidade'],
                    'valor_unitario' => $item['valor_unitario'],
                    'desconto' => $item['desconto'] ?? 0,
                    'valor_total' => $itemTotal,
                ]);
                $total += $itemTotal;
            }

            $pedido->update(['valor_total' => $total - ($data['desconto'] ?? 0)]);

            return $pedido;
        });
    }

    public function updatePedido(Pedido $pedido, array $data): Pedido
    {
        return DB::transaction(function () use ($pedido, $data) {
            $pedido->update(collect($data)->except('itens')->toArray());

            if (isset($data['itens'])) {
                $pedido->itens()->delete();

                $total = 0;
                foreach ($data['itens'] as $item) {
                    $itemTotal = ($item['quantidade'] * $item['valor_unitario']) - ($item['desconto'] ?? 0);
                    PedidoItem::create([
                        'pedido_id' => $pedido->id,
                        'produto_id' => $item['produto_id'] ?? null,
                        'descricao' => $item['descricao'],
                        'quantidade' => $item['quantidade'],
                        'valor_unitario' => $item['valor_unitario'],
                        'desconto' => $item['desconto'] ?? 0,
                        'valor_total' => $itemTotal,
                    ]);
                    $total += $itemTotal;
                }

                $pedido->update(['valor_total' => $total - ($pedido->desconto ?? 0)]);
            }

            return $pedido->fresh();
        });
    }
}
