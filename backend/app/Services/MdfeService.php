<?php

namespace App\Services;

use App\Models\Mdfe;
use App\Models\MdfeDocumento;
use Illuminate\Support\Facades\DB;

class MdfeService
{
    public function create(array $data, $company): Mdfe
    {
        return DB::transaction(function () use ($data, $company) {
            $mdfe = Mdfe::create([
                'company_id' => $company->id,
                'veiculo_id' => $data['veiculo_id'] ?? null,
                'motorista_id' => $data['motorista_id'] ?? null,
                'modelo' => 58,
                'serie' => $data['serie'] ?? ($company->serie_mdfe ?? 1),
                'numero' => $data['numero'] ?? ($company->proximo_numero_mdfe ?? 1),
                'modal' => $data['modal'] ?? 1,
                'data_emissao' => $data['data_emissao'] ?? now()->toDateString(),
                'status' => 'rascunho',
                'ambiente' => $company->ambiente === 'producao' ? 1 : 2,
                'uf_carregamento' => $data['uf_carregamento'] ?? '',
                'uf_descarregamento' => $data['uf_descarregamento'] ?? '',
                'municipio_carregamento' => $data['municipio_carregamento'] ?? null,
                'municipio_carregamento_ibge' => $data['municipio_carregamento_ibge'] ?? null,
                'municipio_descarregamento' => $data['municipio_descarregamento'] ?? null,
                'municipio_descarregamento_ibge' => $data['municipio_descarregamento_ibge'] ?? null,
                'veiculo_placa' => $data['veiculo_placa'] ?? null,
                'motorista_cpf' => $data['motorista_cpf'] ?? null,
                'motorista_nome' => $data['motorista_nome'] ?? null,
                'valor_carga' => $data['valor_carga'] ?? 0,
                'peso_bruto' => $data['peso_bruto'] ?? 0,
                'uf_percurso' => $data['uf_percurso'] ?? [],
                'informacoes_adicionais' => $data['informacoes_adicionais'] ?? null,
            ]);

            foreach ($data['documentos'] ?? [] as $doc) {
                MdfeDocumento::create([
                    'mdfe_id' => $mdfe->id,
                    'tipo' => $doc['tipo'],
                    'chave' => $doc['chave'],
                ]);
            }

            if (isset($data['numero'])) {
                $nextNumero = (int) $data['numero'] + 1;
                $company->update(['proximo_numero_mdfe' => $nextNumero]);
            }

            return $mdfe->load('documentos', 'veiculo', 'motorista');
        });
    }

    public function update(Mdfe $mdfe, array $data): Mdfe
    {
        return DB::transaction(function () use ($mdfe, $data) {
            $mdfe->update(collect($data)->except(['documentos'])->toArray());

            if (isset($data['documentos'])) {
                $mdfe->documentos()->delete();
                foreach ($data['documentos'] as $doc) {
                    MdfeDocumento::create([
                        'mdfe_id' => $mdfe->id,
                        'tipo' => $doc['tipo'],
                        'chave' => $doc['chave'],
                    ]);
                }
            }

            return $mdfe->fresh('documentos', 'veiculo', 'motorista');
        });
    }
}
