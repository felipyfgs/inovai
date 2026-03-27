<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->string('serie', 3)->default('1');
            $table->bigInteger('numero');
            $table->string('chave', 44)->nullable()->unique();
            $table->string('codigo_verificacao', 8)->nullable();
            $table->date('data_emissao');
            $table->date('data_competencia');
            $table->enum('status', ['rascunho', 'assinada', 'transmitida', 'autorizada', 'rejeitada', 'cancelada'])->default('rascunho');
            $table->tinyInteger('ambiente')->default(2);
            $table->string('natureza_operacao', 100);
            $table->string('codigo_servico', 20);
            $table->text('descricao_servico');
            $table->decimal('valor_servico', 15, 2);
            $table->decimal('valor_deducoes', 15, 2)->default(0);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_ir', 15, 2)->default(0);
            $table->decimal('valor_inss', 15, 2)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);
            $table->decimal('valor_csll', 15, 2)->default(0);
            $table->decimal('valor_outras', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2);
            $table->string('cnae', 7)->nullable();
            $table->string('cidade_ibge', 7);
            $table->string('cidade', 100);
            $table->string('uf', 2);
            $table->string('tomador_nome')->nullable();
            $table->string('tomador_cpf_cnpj')->nullable();
            $table->string('tomador_logradouro')->nullable();
            $table->string('tomador_numero')->nullable();
            $table->string('tomador_bairro')->nullable();
            $table->string('tomador_cep')->nullable();
            $table->string('tomador_municipio')->nullable();
            $table->string('tomador_uf')->nullable();
            $table->string('tomador_email')->nullable();
            $table->string('tomador_telefone')->nullable();
            $table->string('protocolo')->nullable();
            $table->text('motivo')->nullable();
            $table->text('informacoes_adicionais')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'numero', 'serie']);
            $table->unique(['company_id', 'serie', 'numero']);
        });

        Schema::create('nfse_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nfse_id')->constrained('nfses')->cascadeOnDelete();
            $table->integer('numero_item');
            $table->string('discriminacao', 500);
            $table->decimal('quantidade', 15, 4)->default(1);
            $table->string('unidade', 20)->nullable();
            $table->decimal('valor_unitario', 15, 4);
            $table->decimal('valor_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('nfse_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nfse_id')->constrained('nfses')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->integer('sequencia')->default(1);
            $table->string('protocolo')->nullable();
            $table->text('justificativa')->nullable();
            $table->text('xml_envio')->nullable();
            $table->text('xml_retorno')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_eventos');
        Schema::dropIfExists('nfse_itens');
        Schema::dropIfExists('nfses');
    }
};
