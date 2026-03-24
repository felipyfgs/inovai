<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pessoas_razao_social ON pessoas (company_id, razao_social varchar_pattern_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_produtos_codigo_barras ON produtos (company_id, codigo_barras)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_orcamentos_data ON orcamentos (company_id, data DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_pedidos_data ON pedidos (company_id, data DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notas_fiscais_data_emissao ON notas_fiscais (company_id, data_emissao)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ctes_data_emissao ON ctes (company_id, data_emissao)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_mdfes_data_emissao ON mdfes (company_id, data_emissao)');
    }

    public function down(): void
    {
        Schema::dropIndexIfExists('idx_pessoas_razao_social');
        Schema::dropIndexIfExists('idx_produtos_codigo_barras');
        Schema::dropIndexIfExists('idx_orcamentos_data');
        Schema::dropIndexIfExists('idx_pedidos_data');
        Schema::dropIndexIfExists('idx_notas_fiscais_data_emissao');
        Schema::dropIndexIfExists('idx_ctes_data_emissao');
        Schema::dropIndexIfExists('idx_mdfes_data_emissao');
    }
};
