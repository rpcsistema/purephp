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
        Schema::create('financial_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('financial_categories');
            $table->decimal('planned_amount', 15, 2);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->decimal('alert_percentage', 5, 2)->default(80.00); // Alert when 80% spent
            $table->boolean('alert_enabled')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['category_id', 'period_type']);
            $table->index(['start_date', 'end_date']);
            $table->index(['is_active', 'period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_budgets');
    }
};