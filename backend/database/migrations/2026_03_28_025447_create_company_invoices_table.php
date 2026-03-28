<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('office_plan_id')->constrained('office_plans')->nullOnDelete();
            $table->string('status', 20)->default('pending')
                ->comment('pending, paid, cancelled, overdue');
            $table->decimal('amount', 10, 2);
            $table->string('reference')->nullable()->comment('Ex: 2026-04');
            $table->text('notes')->nullable();
            $table->timestamp('due_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['office_id', 'status']);
            $table->index('due_at');
        });

        Schema::create('company_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_invoice_id')->constrained('company_invoices')->cascadeOnDelete();
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_invoice_items');
        Schema::dropIfExists('company_invoices');
    }
};
