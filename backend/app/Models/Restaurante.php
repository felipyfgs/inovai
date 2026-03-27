<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestauranteMesa extends Model
{
    protected $table = 'restaurante_mesas';

    protected $fillable = [
        'company_id',
        'nome',
        'capacidade',
        'status',
        'localizacao',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function comandas(): HasMany
    {
        return $this->hasMany(RestauranteComanda::class, 'mesa_id');
    }
}

class RestauranteCardapioGrupo extends Model
{
    protected $table = 'restaurante_cardapio_grupos';

    protected $fillable = [
        'company_id',
        'nome',
        'ordem',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(RestauranteCardapioItem::class, 'grupo_id');
    }
}

class RestauranteCardapioItem extends Model
{
    protected $table = 'restaurante_cardapio_itens';

    protected $fillable = [
        'company_id',
        'grupo_id',
        'nome',
        'descricao',
        'preco',
        'imagem_url',
        'is_active',
        'disponivel',
        'codigo',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'is_active' => 'boolean',
            'disponivel' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(RestauranteCardapioGrupo::class, 'grupo_id');
    }
}

class RestauranteComanda extends Model
{
    protected $table = 'restaurante_comandas';

    protected $fillable = [
        'company_id',
        'mesa_id',
        'garcom_id',
        'codigo',
        'status',
        'valor_total',
        'desconto',
        'opened_at',
        'closed_at',
        'pessoas',
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'desconto' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(RestauranteMesa::class, 'mesa_id');
    }

    public function garcom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'garcom_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(RestauranteComandaItem::class, 'comanda_id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(RestaurantePagamento::class, 'comanda_id');
    }

    public static function gerarCodigo(): string
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    }

    public function recalcularTotal(): void
    {
        $this->update([
            'valor_total' => $this->itens()
                ->whereNotIn('status', ['cancelado'])
                ->sum('valor_total'),
        ]);
    }
}

class RestauranteComandaItem extends Model
{
    protected $table = 'restaurante_comanda_itens';

    protected $fillable = [
        'comanda_id',
        'item_id',
        'quantidade',
        'preco_unitario',
        'valor_total',
        'observacoes',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'preco_unitario' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(RestauranteComanda::class, 'comanda_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(RestauranteCardapioItem::class, 'item_id');
    }
}

class RestaurantePagamento extends Model
{
    protected $table = 'restaurante_pagamentos';

    protected $fillable = [
        'comanda_id',
        'forma_pagamento',
        'valor',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function comanda(): BelongsTo
    {
        return $this->belongsTo(RestauranteComanda::class, 'comanda_id');
    }
}
