<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nfse extends Model
{
    protected $fillable = [
        'company_id',
        'pessoa_id',
        'serie',
        'numero',
        'chave',
        'codigo_verificacao',
        'data_emissao',
        'data_competencia',
        'status',
        'ambiente',
        'natureza_operacao',
        'codigo_servico',
        'descricao_servico',
        'valor_servico',
        'valor_deducoes',
        'valor_desconto',
        'valor_ir',
        'valor_inss',
        'valor_pis',
        'valor_cofins',
        'valor_csll',
        'valor_outras',
        'valor_total',
        'cnae',
        'cidade_ibge',
        'cidade',
        'uf',
        'tomador_nome',
        'tomador_cpf_cnpj',
        'tomador_logradouro',
        'tomador_numero',
        'tomador_bairro',
        'tomador_cep',
        'tomador_municipio',
        'tomador_uf',
        'tomador_email',
        'tomador_telefone',
        'protocolo',
        'motivo',
        'informacoes_adicionais',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'data_competencia' => 'date',
            'valor_servico' => 'decimal:2',
            'valor_deducoes' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'valor_ir' => 'decimal:2',
            'valor_inss' => 'decimal:2',
            'valor_pis' => 'decimal:2',
            'valor_cofins' => 'decimal:2',
            'valor_csll' => 'decimal:2',
            'valor_outras' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(NfseItem::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(NfseEvento::class);
    }

    public static function gerarCodigoVerificacao(): string
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    }
}
