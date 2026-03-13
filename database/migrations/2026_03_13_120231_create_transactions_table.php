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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Creates user_id column and sets up a foreign key relation to users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Transaction category (e.g., 'food', 'salary'), defaults to 'other'
            $table->foreignId('category_id')->constrained()->onDelete('cascade');

            // Transaction amount: total 10 digits, 2 decimal places (e.g., 99999999.99)
            $table->decimal('amount', 10, 2);

            // Transaction description (optional)
            $table->string('description')->nullable();

            // Transaction type: 'income' or 'expense'
            $table->enum('type', ['income', 'expense']);

            // Date of the transaction, defaults to current timestamp
            $table->timestamp('transaction_date')->useCurrent();

            // standard Laravel created_at and updated_at columns
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
