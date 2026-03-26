<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('grace_period_days')->default(7)->after('is_active');
            $table->unsignedInteger('max_overdue_days')->default(30)->after('grace_period_days');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['grace_period_days', 'max_overdue_days']);
        });
    }
};
