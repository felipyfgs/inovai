<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->timestamp('inactivated_at')->nullable()->after('is_active');
            $table->string('inactivation_reason')->nullable()->after('inactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['inactivated_at', 'inactivation_reason']);
        });
    }
};
