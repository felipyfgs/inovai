<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['pagar', 'receber']);
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->cascadeOnDelete();
            $table->string('descricao');
            $table->string('documento')->nullable();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->string('categoria')->nullable();
            $table->date('data_emissao');
            $table->date('data_vencimento');
            $table->date('data_baixa')->nullable();
            $table->decimal('valor_original', 15, 2);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_juros', 15, 2)->default(0);
            $table->decimal('valor_multa', 15, 2)->default(0);
            $table->decimal('valor_baixado', 15, 2)->default(0);
            $table->enum('status', ['pendente', 'pago_parcial', 'pago', 'vencido', 'cancelado'])->default('pendente');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'tipo']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'data_vencimento']);
        });

        Schema::create('contas_parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->integer('numero');
            $table->date('data_vencimento');
            $table->date('data_baixa')->nullable();
            $table->decimal('valor', 15, 2);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_juros', 15, 2)->default(0);
            $table->decimal('valor_multa', 15, 2)->default(0);
            $table->decimal('valor_baixado', 15, 2)->default(0);
            $table->string('forma_pagamento')->nullable();
            $table->text('observacoes')->nullable();
            $table->enum('status', ['pendente', 'pago_parcial', 'pago'])->default('pendente');
            $table->timestamps();

            $table->index(['conta_id', 'status']);
        });

        Schema::create('contas_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
            $table->foreignId('parcela_id')->nullable()->constrained('contas_parcelas')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'saida']);
            $table->decimal('valor', 15, 2);
            $table->string('forma_pagamento')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_movimentacoes');
        Schema::dropIfExists('contas_parcelas');
        Schema::dropIfExists('contas');
    }
};
