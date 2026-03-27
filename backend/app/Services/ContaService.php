<?php

namespace App\Services;

use App\Models\Conta;
use App\Models\ContaMovimentacao;
use App\Models\ContaParcela;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContaService
{
    public function create(array $data): Conta
    {
        return DB::transaction(function () use ($data) {
            $company = app('current_company');
            $data['company_id'] = $company->id;

            $conta = Conta::create($data);

            if (! empty($data['parcelas']) && count($data['parcelas']) > 1) {
                foreach ($data['parcelas'] as $index => $parcela) {
                    ContaParcela::create([
                        'conta_id' => $conta->id,
                        'numero' => $index + 1,
                        'data_vencimento' => $parcela['data_vencimento'],
                        'valor' => $parcela['valor'],
                    ]);
                }
            } elseif (! empty($data['parcelas']) && count($data['parcelas']) === 1) {
                ContaParcela::create([
                    'conta_id' => $conta->id,
                    'numero' => 1,
                    'data_vencimento' => $data['data_vencimento'],
                    'valor' => $data['valor_original'],
                ]);
            } else {
                ContaParcela::create([
                    'conta_id' => $conta->id,
                    'numero' => 1,
                    'data_vencimento' => $data['data_vencimento'],
                    'valor' => $data['valor_original'],
                ]);
            }

            $conta->load('parcelas');
            $conta->parcelas_count = $conta->parcelas->count();

            return $conta;
        });
    }

    public function update(Conta $conta, array $data): Conta
    {
        $conta->update($data);

        return $conta->load('parcelas', 'pessoa');
    }

    public function baixarParcela(ContaParcela $parcela, array $data): ContaParcela
    {
        return DB::transaction(function () use ($parcela, $data) {
            $valor = (float) $data['valor'];
            $desconto = (float) ($data['valor_desconto'] ?? 0);
            $juros = (float) ($data['valor_juros'] ?? 0);
            $multa = (float) ($data['valor_multa'] ?? 0);

            $parcela->update([
                'valor_baixado' => $valor,
                'valor_desconto' => $desconto,
                'valor_juros' => $juros,
                'valor_multa' => $multa,
                'forma_pagamento' => $data['forma_pagamento'] ?? null,
                'observacoes' => $data['observacoes'] ?? null,
                'data_baixa' => now()->toDateString(),
                'status' => $valor >= ((float) $parcela->valor - $desconto) ? 'pago' : 'pago_parcial',
            ]);

            $conta = $parcela->conta;

            ContaMovimentacao::create([
                'conta_id' => $conta->id,
                'parcela_id' => $parcela->id,
                'user_id' => Auth::id(),
                'tipo' => $conta->tipo === 'receber' ? 'entrada' : 'saida',
                'valor' => $valor,
                'forma_pagamento' => $data['forma_pagamento'] ?? null,
                'observacoes' => $data['observacoes'] ?? null,
                'data' => now(),
            ]);

            $this->recalcularConta($conta);

            return $parcela;
        });
    }

    public function cancelar(Conta $conta): Conta
    {
        return DB::transaction(function () use ($conta) {
            if ($conta->status === 'pago') {
                abort(422, 'Não é possível cancelar uma conta já paga.');
            }

            $conta->update(['status' => 'cancelado']);

            return $conta;
        });
    }

    public function estornarBaixa(ContaParcela $parcela): ContaParcela
    {
        return DB::transaction(function () use ($parcela) {
            $parcela->update([
                'valor_baixado' => 0,
                'valor_desconto' => 0,
                'valor_juros' => 0,
                'valor_multa' => 0,
                'forma_pagamento' => null,
                'data_baixa' => null,
                'status' => 'pendente',
            ]);

            $this->recalcularConta($parcela->conta);

            return $parcela;
        });
    }

    public function getQuery($company, $request)
    {
        $query = $company->contas()->with('pessoa');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('descricao', 'ilike', "%{$search}%")
                    ->orWhere('documento', 'ilike', "%{$search}%")
                    ->orWhereHas('pessoa', function ($pq) use ($search) {
                        $pq->where('razao_social', 'ilike', "%{$search}%")
                            ->orWhere('cpf_cnpj', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->filled('data_inicio')) {
            $query->where('data_vencimento', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->where('data_vencimento', '<=', $request->input('data_fim'));
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        $query->orderBy('data_vencimento', 'desc');

        return $query;
    }

    public function getResumo($company): array
    {
        $aPagar = Conta::where('company_id', $company->id)
            ->where('tipo', 'pagar')
            ->whereIn('status', ['pendente', 'pago_parcial', 'vencido'])
            ->sumRaw('COALESCE(valor_original - valor_baixado, 0)');

        $aReceber = Conta::where('company_id', $company->id)
            ->where('tipo', 'receber')
            ->whereIn('status', ['pendente', 'pago_parcial', 'vencido'])
            ->sumRaw('COALESCE(valor_original - valor_baixado, 0)');

        $vencido = Conta::where('company_id', $company->id)
            ->where('status', 'vencido')
            ->sumRaw('COALESCE(valor_original - valor_baixado, 0)');

        $aVencer30 = Conta::where('company_id', $company->id)
            ->whereIn('status', ['pendente', 'pago_parcial'])
            ->whereBetween('data_vencimento', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->sumRaw('COALESCE(valor_original - valor_baixado, 0)');

        return [
            'total_a_pagar' => (float) $aPagar,
            'total_a_receber' => (float) $aReceber,
            'total_vencido' => (float) $vencido,
            'total_a_vencer_30' => (float) $aVencer30,
            'saldo_previsto' => (float) ($aReceber - $aPagar),
        ];
    }

    private function recalcularConta(Conta $conta): void
    {
        $totalBaixado = $conta->parcelas()->sum('valor_baixado');
        $totalDesconto = $conta->parcelas()->sum('valor_desconto');

        $conta->update([
            'valor_baixado' => $totalBaixado,
            'valor_desconto' => $totalDesconto,
        ]);

        $valorRestante = (float) bcsub($conta->valor_original, $totalBaixado, 2);

        if ($valorRestante <= 0) {
            $conta->update([
                'status' => 'pago',
                'data_baixa' => now()->toDateString(),
            ]);
        } elseif ($totalBaixado > 0) {
            $conta->update(['status' => 'pago_parcial']);
        } elseif ($conta->data_vencimento->isPast()) {
            $conta->update(['status' => 'vencido']);
        } else {
            $conta->update(['status' => 'pendente']);
        }
    }
}
