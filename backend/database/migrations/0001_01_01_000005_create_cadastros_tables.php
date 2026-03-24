<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('tipo', 20)->default('cliente')->comment('cliente, fornecedor, ambos');
            $table->string('tipo_pessoa', 2)->default('PF')->comment('PF ou PJ');
            $table->string('razao_social');
            $table->string('fantasia')->nullable();
            $table->string('cpf_cnpj', 18)->nullable();
            $table->string('ie', 20)->nullable();
            $table->string('im', 20)->nullable();
            $table->tinyInteger('ind_ie')->default(9)->comment('1=Contribuinte, 2=Isento, 9=Nao Contribuinte');

            $table->string('logradouro')->nullable();
            $table->string('numero', 10)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('municipio')->nullable();
            $table->string('municipio_ibge', 7)->nullable();
            $table->char('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('pais', 50)->default('Brasil');
            $table->string('pais_ibge', 4)->default('1058');

            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'tipo']);
            $table->index(['company_id', 'cpf_cnpj']);
        });

        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('codigo', 60)->nullable();
            $table->string('codigo_barras', 14)->nullable();
            $table->string('descricao');
            $table->string('ncm', 8)->nullable();
            $table->string('cest', 7)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('unidade', 6)->default('UN');
            $table->decimal('preco_venda', 15, 4)->default(0);
            $table->decimal('preco_custo', 15, 4)->default(0);
            $table->tinyInteger('origem')->default(0)->comment('0=Nacional, 1=Estrangeira importacao, 2=Estrangeira mercado interno...');

            // Tributacao
            $table->string('cst_icms', 3)->nullable();
            $table->string('csosn', 4)->nullable();
            $table->decimal('aliq_icms', 5, 2)->default(0);
            $table->decimal('aliq_ipi', 5, 2)->default(0);
            $table->string('cst_pis', 2)->nullable();
            $table->decimal('aliq_pis', 5, 2)->default(0);
            $table->string('cst_cofins', 2)->nullable();
            $table->decimal('aliq_cofins', 5, 2)->default(0);

            $table->decimal('peso_liquido', 12, 3)->default(0);
            $table->decimal('peso_bruto', 12, 3)->default(0);
            $table->decimal('estoque_minimo', 15, 4)->default(0);
            $table->text('observacoes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'codigo']);
            $table->index(['company_id', 'descricao']);
        });

        Schema::create('transportadoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('fantasia')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('ie', 20)->nullable();
            $table->string('rntrc', 20)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 10)->nullable();
            $table->string('bairro')->nullable();
            $table->string('municipio')->nullable();
            $table->string('municipio_ibge', 7)->nullable();
            $table->char('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });

        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('transportadora_id')->nullable()->constrained('transportadoras')->nullOnDelete();
            $table->string('placa', 8);
            $table->string('renavam', 20)->nullable();
            $table->char('uf', 2)->nullable();
            $table->decimal('tara', 10, 2)->default(0);
            $table->decimal('capacidade_kg', 10, 2)->default(0);
            $table->string('tipo_veiculo', 30)->nullable();
            $table->string('tipo_carroceria', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });

        Schema::create('motoristas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('nome');
            $table->string('cpf', 14)->nullable();
            $table->string('cnh', 20)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motoristas');
        Schema::dropIfExists('veiculos');
        Schema::dropIfExists('transportadoras');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('pessoas');
    }
};
