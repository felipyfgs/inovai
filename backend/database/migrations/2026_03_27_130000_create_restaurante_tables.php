<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurante_mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('nome', 50);
            $table->integer('capacidade')->default(4);
            $table->enum('status', ['livre', 'ocupada', 'reservada', 'inativa'])->default('livre');
            $table->text('localizacao')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('restaurante_cardapio_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('nome', 100);
            $table->integer('ordem')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('restaurante_cardapio_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('restaurante_cardapio_grupos')->cascadeOnDelete();
            $table->string('nome', 200);
            $table->text('descricao')->nullable();
            $table->decimal('preco', 15, 2);
            $table->string('imagem_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('disponivel')->default(true);
            $table->string('codigo', 20)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active', 'disponivel']);
        });

        Schema::create('restaurante_comandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mesa_id')->nullable()->constrained('restaurante_mesas')->nullOnDelete();
            $table->foreignId('garcom_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('codigo', 20)->unique();
            $table->enum('status', ['aberta', 'fechada', 'cancelada'])->default('aberta');
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->decimal('desconto', 15, 2)->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->integer('pessoas')->default(1);
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'opened_at']);
        });

        Schema::create('restaurante_comanda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('restaurante_comandas')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('restaurante_cardapio_itens')->cascadeOnDelete();
            $table->integer('quantidade')->default(1);
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('valor_total', 15, 2);
            $table->text('observacoes')->nullable();
            $table->enum('status', ['pendente', 'preparando', 'pronto', 'entregue', 'cancelado'])->default('pendente');
            $table->timestamps();

            $table->index(['comanda_id', 'status']);
        });

        Schema::create('restaurante_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('restaurante_comandas')->cascadeOnDelete();
            $table->string('forma_pagamento', 50);
            $table->decimal('valor', 15, 2);
            $table->string('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurante_pagamentos');
        Schema::dropIfExists('restaurante_comanda_itens');
        Schema::dropIfExists('restaurante_comandas');
        Schema::dropIfExists('restaurante_cardapio_itens');
        Schema::dropIfExists('restaurante_cardapio_grupos');
        Schema::dropIfExists('restaurante_mesas');
    }
};