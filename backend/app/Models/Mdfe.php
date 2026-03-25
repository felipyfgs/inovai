<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mdfe extends Model
{
    protected $table = 'mdfes';

    protected $fillable = [
        'company_id',
        'veiculo_id',
        'motorista_id',
        'modelo',
        'serie',
        'numero',
        'chave',
        'modal',
        'data_emissao',
        'status',
        'ambiente',
        'uf_carregamento',
        'uf_descarregamento',
        'municipio_carregamento',
        'municipio_carregamento_ibge',
        'municipio_descarregamento',
        'municipio_descarregamento_ibge',
        'veiculo_placa',
        'motorista_cpf',
        'motorista_nome',
        'valor_carga',
        'peso_bruto',
        'xml_envio',
        'xml_retorno',
        'xml_autorizado',
        'protocolo',
        'motivo',
        'uf_percurso',
        'informacoes_adicionais',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'valor_carga' => 'decimal:2',
            'peso_bruto' => 'decimal:4',
            'uf_percurso' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(MdfeDocumento::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(MdfeEvento::class);
    }
}
