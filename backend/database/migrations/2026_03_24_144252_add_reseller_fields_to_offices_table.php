<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('type', 20)->default('contador')->after('email')
                ->comment('admin, contador, direct');
            $table->boolean('is_reseller')->default(false)->after('type');
            $table->decimal('reseller_commission', 5, 2)->default(0)->after('is_reseller');
            $table->foreignId('parent_office_id')->nullable()->after('reseller_commission')
                ->constrained('offices')->nullOnDelete();
            $table->text('notes')->nullable()->after('parent_office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropForeign(['parent_office_id']);
            $table->dropColumn(['type', 'is_reseller', 'reseller_commission', 'parent_office_id', 'notes']);
        });
    }
};
