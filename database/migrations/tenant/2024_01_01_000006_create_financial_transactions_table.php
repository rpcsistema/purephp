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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['revenue', 'expense']);
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->foreignId('category_id')->constrained('financial_categories');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'pix', 'other'])->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // Store file paths
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->foreignId('parent_transaction_id')->nullable()->constrained('financial_transactions')->onDelete('cascade');
            $table->timestamps();

            $table->index(['type', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
            $table->index(['user_id', 'transaction_date']);
            $table->index(['status', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};