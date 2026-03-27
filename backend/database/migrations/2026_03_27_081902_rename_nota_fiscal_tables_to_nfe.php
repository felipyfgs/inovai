<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('notas_fiscais', 'nfes');
        Schema::rename('nota_fiscal_itens', 'nfe_itens');
        Schema::rename('nota_fiscal_eventos', 'nfe_eventos');

        Schema::table('nfe_itens', function (Blueprint $table) {
            $table->renameColumn('nota_fiscal_id', 'nfe_id');
        });

        Schema::table('nfe_eventos', function (Blueprint $table) {
            $table->renameColumn('nota_fiscal_id', 'nfe_id');
        });

        DB::statement('DROP INDEX IF EXISTS idx_notas_fiscais_data_emissao');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_nfes_data_emissao ON nfes (company_id, data_emissao)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_nfes_data_emissao');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notas_fiscais_data_emissao ON notas_fiscais (company_id, data_emissao)');

        Schema::table('nfe_eventos', function (Blueprint $table) {
            $table->renameColumn('nfe_id', 'nota_fiscal_id');
        });

        Schema::table('nfe_itens', function (Blueprint $table) {
            $table->renameColumn('nfe_id', 'nota_fiscal_id');
        });

        Schema::rename('nfe_eventos', 'nota_fiscal_eventos');
        Schema::rename('nfe_itens', 'nota_fiscal_itens');
        Schema::rename('nfes', 'notas_fiscais');
    }
};
