<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'office_id',
        'razao_social',
        'fantasia',
        'cnpj',
        'ie',
        'im',
        'crt',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'municipio',
        'municipio_ibge',
        'uf',
        'cep',
        'pais',
        'pais_ibge',
        'telefone',
        'email',
        'certificado_pfx',
        'certificado_senha',
        'certificado_validade',
        'ambiente',
        'serie_nfe',
        'proximo_numero_nfe',
        'serie_nfce',
        'proximo_numero_nfce',
        'serie_cte',
        'proximo_numero_cte',
        'serie_mdfe',
        'proximo_numero_mdfe',
        'csc_id',
        'csc_token',
        'is_active',
    ];

    protected $hidden = [
        'certificado_pfx',
        'certificado_senha',
    ];

    protected function casts(): array
    {
        return [
            'certificado_validade' => 'date',
            'is_active' => 'boolean',
            'certificado_pfx' => 'encrypted',
            'certificado_senha' => 'encrypted',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')->withTimestamps();
    }

    public function pessoas(): HasMany
    {
        return $this->hasMany(Pessoa::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    public function transportadoras(): HasMany
    {
        return $this->hasMany(Transportadora::class);
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }

    public function motoristas(): HasMany
    {
        return $this->hasMany(Motorista::class);
    }

    public function orcamentos(): HasMany
    {
        return $this->hasMany(Orcamento::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function notasFiscais(): HasMany
    {
        return $this->hasMany(NotaFiscal::class);
    }

    public function ctes(): HasMany
    {
        return $this->hasMany(Cte::class);
    }

    public function mdfes(): HasMany
    {
        return $this->hasMany(Mdfe::class);
    }

    public function estoques(): HasMany
    {
        return $this->hasMany(Estoque::class);
    }
}
