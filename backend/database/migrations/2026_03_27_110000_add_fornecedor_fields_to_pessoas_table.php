<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pessoas', function (Blueprint $table) {
            $table->string('condicao_pagamento')->nullable()->after('observacoes');
            $table->integer('prazo_entrega')->nullable()->after('condicao_pagamento');
            $table->tinyInteger('avaliacao')->nullable()->after('prazo_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('pessoas', function (Blueprint $table) {
            $table->dropColumn(['condicao_pagamento', 'prazo_entrega', 'avaliacao']);
        });
    }
};
