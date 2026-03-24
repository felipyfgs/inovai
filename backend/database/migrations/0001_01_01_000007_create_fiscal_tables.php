<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NF-e / NFC-e
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->foreignId('transportadora_id')->nullable()->constrained('transportadoras')->nullOnDelete();
            $table->tinyInteger('modelo')->default(55)->comment('55=NF-e, 65=NFC-e');
            $table->integer('serie')->default(1);
            $table->integer('numero');
            $table->string('chave', 44)->nullable()->unique();
            $table->string('natureza_operacao')->default('Venda de Mercadoria');
            $table->tinyInteger('tipo_operacao')->default(1)->comment('0=Entrada, 1=Saida');
            $table->tinyInteger('finalidade')->default(1)->comment('1=Normal, 2=Complementar, 3=Ajuste, 4=Devolucao');
            $table->date('data_emissao');
            $table->timestamp('data_saida')->nullable();
            $table->string('status', 20)->default('rascunho')->comment('rascunho, assinada, transmitida, autorizada, rejeitada, cancelada, inutilizada, denegada');
            $table->tinyInteger('ambiente')->default(2)->comment('1=Producao, 2=Homologacao');

            // Totais
            $table->decimal('valor_produtos', 15, 2)->default(0);
            $table->decimal('valor_frete', 15, 2)->default(0);
            $table->decimal('valor_seguro', 15, 2)->default(0);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_outras', 15, 2)->default(0);
            $table->decimal('valor_icms', 15, 2)->default(0);
            $table->decimal('valor_icms_st', 15, 2)->default(0);
            $table->decimal('valor_ipi', 15, 2)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);

            // Transporte
            $table->tinyInteger('frete_por')->default(9)->comment('0=Emitente, 1=Destinatario, 2=Terceiros, 9=Sem frete');

            // SEFAZ
            $table->text('xml_envio')->nullable();
            $table->text('xml_retorno')->nullable();
            $table->text('xml_autorizado')->nullable();
            $table->string('protocolo', 20)->nullable();
            $table->string('recibo', 20)->nullable();
            $table->text('motivo')->nullable();

            // Informacoes complementares
            $table->text('informacoes_adicionais')->nullable();
            $table->text('informacoes_fisco')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'modelo', 'status']);
            $table->index(['company_id', 'numero', 'serie', 'modelo']);
        });

        Schema::create('nota_fiscal_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_fiscal_id')->constrained('notas_fiscais')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->integer('numero_item')->default(1);
            $table->string('codigo', 60)->nullable();
            $table->string('descricao');
            $table->string('ncm', 8)->nullable();
            $table->string('cest', 7)->nullable();
            $table->string('cfop', 4);
            $table->string('unidade', 6)->default('UN');
            $table->decimal('quantidade', 15, 4)->default(1);
            $table->decimal('valor_unitario', 15, 4)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_frete', 15, 2)->default(0);
            $table->decimal('valor_seguro', 15, 2)->default(0);
            $table->decimal('valor_outras', 15, 2)->default(0);

            // ICMS
            $table->tinyInteger('origem')->default(0);
            $table->string('cst_icms', 3)->nullable();
            $table->string('csosn', 4)->nullable();
            $table->decimal('bc_icms', 15, 2)->default(0);
            $table->decimal('aliq_icms', 5, 2)->default(0);
            $table->decimal('valor_icms', 15, 2)->default(0);
            $table->decimal('bc_icms_st', 15, 2)->default(0);
            $table->decimal('aliq_icms_st', 5, 2)->default(0);
            $table->decimal('valor_icms_st', 15, 2)->default(0);

            // IPI
            $table->string('cst_ipi', 2)->nullable();
            $table->decimal('bc_ipi', 15, 2)->default(0);
            $table->decimal('aliq_ipi', 5, 2)->default(0);
            $table->decimal('valor_ipi', 15, 2)->default(0);

            // PIS
            $table->string('cst_pis', 2)->nullable();
            $table->decimal('bc_pis', 15, 2)->default(0);
            $table->decimal('aliq_pis', 5, 2)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);

            // COFINS
            $table->string('cst_cofins', 2)->nullable();
            $table->decimal('bc_cofins', 15, 2)->default(0);
            $table->decimal('aliq_cofins', 5, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);

            $table->timestamps();
        });

        Schema::create('nota_fiscal_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_fiscal_id')->constrained('notas_fiscais')->cascadeOnDelete();
            $table->string('tipo', 30)->comment('cancelamento, cce, inutilizacao');
            $table->integer('sequencia')->default(1);
            $table->string('protocolo', 20)->nullable();
            $table->text('justificativa')->nullable();
            $table->text('correcao')->nullable();
            $table->text('xml_envio')->nullable();
            $table->text('xml_retorno')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->timestamps();
        });

        // CT-e
        Schema::create('ctes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('remetente_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('destinatario_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('expedidor_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('recebedor_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('tomador_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->tinyInteger('tomador_tipo')->default(0)->comment('0=Remetente, 1=Expedidor, 2=Recebedor, 3=Destinatario');
            $table->integer('modelo')->default(57);
            $table->integer('serie')->default(1);
            $table->integer('numero');
            $table->string('chave', 44)->nullable()->unique();
            $table->string('cfop', 4)->nullable();
            $table->string('natureza_operacao')->default('Prestacao de Servico de Transporte');
            $table->tinyInteger('modal')->default(1)->comment('1=Rodoviario, 2=Aereo, 3=Aquaviario, 4=Ferroviario, 5=Dutoviario');
            $table->date('data_emissao');
            $table->string('status', 20)->default('rascunho');
            $table->tinyInteger('ambiente')->default(2);

            // Valores
            $table->decimal('valor_servico', 15, 2)->default(0);
            $table->decimal('valor_receber', 15, 2)->default(0);
            $table->decimal('valor_icms', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);

            // UF
            $table->char('uf_inicio', 2)->nullable();
            $table->string('municipio_inicio')->nullable();
            $table->string('municipio_inicio_ibge', 7)->nullable();
            $table->char('uf_fim', 2)->nullable();
            $table->string('municipio_fim')->nullable();
            $table->string('municipio_fim_ibge', 7)->nullable();

            // SEFAZ
            $table->text('xml_envio')->nullable();
            $table->text('xml_retorno')->nullable();
            $table->text('xml_autorizado')->nullable();
            $table->string('protocolo', 20)->nullable();
            $table->text('motivo')->nullable();
            $table->text('informacoes_adicionais')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('cte_nfes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cte_id')->constrained('ctes')->cascadeOnDelete();
            $table->string('chave_nfe', 44);
            $table->timestamps();
        });

        Schema::create('cte_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cte_id')->constrained('ctes')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->integer('sequencia')->default(1);
            $table->string('protocolo', 20)->nullable();
            $table->text('justificativa')->nullable();
            $table->text('correcao')->nullable();
            $table->text('xml_envio')->nullable();
            $table->text('xml_retorno')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->timestamps();
        });

        // MDF-e
        Schema::create('mdfes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('veiculo_id')->nullable()->constrained('veiculos')->nullOnDelete();
            $table->foreignId('motorista_id')->nullable()->constrained('motoristas')->nullOnDelete();
            $table->integer('modelo')->default(58);
            $table->integer('serie')->default(1);
            $table->integer('numero');
            $table->string('chave', 44)->nullable()->unique();
            $table->tinyInteger('modal')->default(1);
            $table->date('data_emissao');
            $table->string('status', 20)->default('rascunho');
            $table->tinyInteger('ambiente')->default(2);

            $table->char('uf_carregamento', 2);
            $table->char('uf_descarregamento', 2);
            $table->string('municipio_carregamento')->nullable();
            $table->string('municipio_carregamento_ibge', 7)->nullable();
            $table->string('municipio_descarregamento')->nullable();
            $table->string('municipio_descarregamento_ibge', 7)->nullable();

            $table->string('veiculo_placa', 8)->nullable();
            $table->string('motorista_cpf', 14)->nullable();
            $table->string('motorista_nome')->nullable();

            $table->decimal('valor_carga', 15, 2)->default(0);
            $table->decimal('peso_bruto', 15, 4)->default(0);

            // SEFAZ
            $table->text('xml_envio')->nullable();
            $table->text('xml_retorno')->nullable();
            $table->text('xml_autorizado')->nullable();
            $table->string('protocolo', 20)->nullable();
            $table->text('motivo')->nullable();

            $table->json('uf_percurso')->nullable();
            $table->text('informacoes_adicionais')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('mdfe_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mdfe_id')->constrained('mdfes')->cascadeOnDelete();
            $table->string('tipo', 10)->comment('nfe, cte');
            $table->string('chave', 44);
            $table->timestamps();
        });

        Schema::create('mdfe_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mdfe_id')->constrained('mdfes')->cascadeOnDelete();
            $table->string('tipo', 30)->comment('encerramento, cancelamento, inclusao_condutor, inclusao_dfe');
            $table->integer('sequencia')->default(1);
            $table->string('protocolo', 20)->nullable();
            $table->text('justificativa')->nullable();
            $table->text('xml_envio')->nullable();
            $table->text('xml_retorno')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdfe_eventos');
        Schema::dropIfExists('mdfe_documentos');
        Schema::dropIfExists('mdfes');
        Schema::dropIfExists('cte_eventos');
        Schema::dropIfExists('cte_nfes');
        Schema::dropIfExists('ctes');
        Schema::dropIfExists('nota_fiscal_eventos');
        Schema::dropIfExists('nota_fiscal_itens');
        Schema::dropIfExists('notas_fiscais');
    }
};
