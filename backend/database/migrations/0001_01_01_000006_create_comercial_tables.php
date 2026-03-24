<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->integer('numero')->default(0);
            $table->date('data');
            $table->date('validade')->nullable();
            $table->string('status', 20)->default('rascunho')->comment('rascunho, enviado, aprovado, recusado, convertido');
            $table->text('observacoes')->nullable();
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('orcamento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('descricao')->nullable();
            $table->decimal('quantidade', 15, 4)->default(1);
            $table->decimal('valor_unitario', 15, 4)->default(0);
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
            $table->integer('numero')->default(0);
            $table->date('data');
            $table->string('status', 20)->default('pendente')->comment('pendente, confirmado, faturado, cancelado');
            $table->text('observacoes')->nullable();
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('pedido_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('descricao')->nullable();
            $table->decimal('quantidade', 15, 4)->default(1);
            $table->decimal('valor_unitario', 15, 4)->default(0);
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_itens');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('orcamento_itens');
        Schema::dropIfExists('orcamentos');
    }
};
