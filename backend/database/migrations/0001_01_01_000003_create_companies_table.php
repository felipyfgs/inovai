<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->string('razao_social');
            $table->string('fantasia')->nullable();
            $table->string('cnpj', 18)->unique();
            $table->string('ie', 20)->nullable();
            $table->string('im', 20)->nullable();
            $table->tinyInteger('crt')->default(1)->comment('1=Simples Nacional, 2=Simples Excesso, 3=Normal');

            // Endereco
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
            $table->string('email')->nullable();

            // Certificado Digital A1
            $table->text('certificado_pfx')->nullable()->comment('Base64 encoded encrypted PFX');
            $table->text('certificado_senha')->nullable()->comment('Encrypted password');
            $table->date('certificado_validade')->nullable();

            // Configuracoes fiscais
            $table->string('ambiente', 12)->default('homologacao')->comment('homologacao ou producao');
            $table->integer('serie_nfe')->default(1);
            $table->integer('proximo_numero_nfe')->default(1);
            $table->integer('serie_nfce')->default(1);
            $table->integer('proximo_numero_nfce')->default(1);
            $table->integer('serie_cte')->default(1);
            $table->integer('proximo_numero_cte')->default(1);
            $table->integer('serie_mdfe')->default(1);
            $table->integer('proximo_numero_mdfe')->default(1);
            $table->string('csc_id', 10)->nullable()->comment('CSC ID para NFC-e');
            $table->string('csc_token')->nullable()->comment('CSC Token para NFC-e');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('office_id');
        });

        // Pivot: quais users podem acessar quais companies
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
};
