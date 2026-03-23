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
        Schema::create('planned_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Source account (where money comes from)
            $table->foreignId('account_id')->constrained()->onDelete('cascade');

            // Destination account (only for transfers, e.g., repaying a loan)
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->onDelete('set null');

            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');

            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['income', 'expense', 'transfer']);

            // Schedule logic
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->date('next_payment_date');

            $table->boolean('is_active')->default(true);
            $table->boolean('auto_execute')->default(false); // If true, the system creates the transaction automatically

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planned_transactions');
    }
};
