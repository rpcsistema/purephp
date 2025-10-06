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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['income_statement', 'cash_flow', 'budget_analysis', 'category_analysis', 'custom']);
            $table->json('filters'); // Store report filters (date range, categories, etc.)
            $table->json('data')->nullable(); // Store generated report data
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->string('file_path')->nullable(); // Path to generated file
            $table->enum('status', ['generating', 'completed', 'failed'])->default('generating');
            $table->boolean('is_scheduled')->default(false);
            $table->enum('schedule_frequency', ['daily', 'weekly', 'monthly', 'quarterly'])->nullable();
            $table->json('schedule_config')->nullable(); // Store schedule configuration
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['created_by', 'created_at']);
            $table->index(['is_scheduled', 'next_generation_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};