<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('logradouro')->nullable()->after('email');
            $table->string('numero')->nullable()->after('logradouro');
            $table->string('complemento')->nullable()->after('numero');
            $table->string('bairro')->nullable()->after('complemento');
            $table->string('municipio')->nullable()->after('bairro');
            $table->string('municipio_ibge')->nullable()->after('municipio');
            $table->string('uf', 2)->nullable()->after('municipio_ibge');
            $table->string('cep', 9)->nullable()->after('uf');
            $table->string('ie')->nullable()->after('cep');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn([
                'logradouro',
                'numero',
                'complemento',
                'bairro',
                'municipio',
                'municipio_ibge',
                'uf',
                'cep',
                'ie',
            ]);
        });
    }
};
