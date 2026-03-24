<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->decimal('quantidade', 15, 4)->default(0);
            $table->decimal('custo_medio', 15, 4)->default(0);
            $table->string('localizacao')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'produto_id']);
        });

        Schema::create('estoque_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estoque_id')->constrained('estoques')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 20)->comment('entrada, saida, ajuste');
            $table->decimal('quantidade', 15, 4);
            $table->decimal('custo_unitario', 15, 4)->default(0);
            $table->string('documento_tipo', 30)->nullable()->comment('nfe, pedido, ajuste_manual');
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('data')->useCurrent();
            $table->timestamps();

            $table->index(['estoque_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_movimentacoes');
        Schema::dropIfExists('estoques');
    }
};
