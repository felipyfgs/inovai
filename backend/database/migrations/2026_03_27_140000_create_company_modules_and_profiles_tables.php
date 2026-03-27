<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('modules');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('company_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('module');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_modules');
        Schema::dropIfExists('company_profiles');
    }
};
