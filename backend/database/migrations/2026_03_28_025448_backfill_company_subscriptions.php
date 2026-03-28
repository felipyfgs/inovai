<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $companies = DB::table('companies')
            ->whereNotNull('office_plan_id')
            ->where('is_active', true)
            ->get();

        foreach ($companies as $company) {
            DB::table('company_subscriptions')->insert([
                'company_id' => $company->id,
                'office_plan_id' => $company->office_plan_id,
                'status' => 'active',
                'starts_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('company_subscriptions')->truncate();
    }
};
