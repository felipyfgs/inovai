<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cte extends Model
{
    protected $fillable = [
        'company_id',
        'remetente_id',
        'destinatario_id',
        'expedidor_id',
        'recebedor_id',
        'tomador_id',
        'tomador_tipo',
        'modelo',
        'serie',
        'numero',
        'chave',
        'cfop',
        'natureza_operacao',
        'modal',
        'data_emissao',
        'status',
        'ambiente',
        'valor_servico',
        'valor_receber',
        'valor_icms',
        'valor_total',
        'uf_inicio',
        'municipio_inicio',
        'municipio_inicio_ibge',
        'uf_fim',
        'municipio_fim',
        'municipio_fim_ibge',
        'xml_envio',
        'xml_retorno',
        'xml_autorizado',
        'protocolo',
        'motivo',
        'informacoes_adicionais',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'valor_servico' => 'decimal:2',
            'valor_receber' => 'decimal:2',
            'valor_icms' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'remetente_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'destinatario_id');
    }

    public function expedidor(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'expedidor_id');
    }

    public function recebedor(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'recebedor_id');
    }

    public function tomador(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'tomador_id');
    }

    public function nfes(): HasMany
    {
        return $this->hasMany(CteNfe::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(CteEvento::class);
    }
}
