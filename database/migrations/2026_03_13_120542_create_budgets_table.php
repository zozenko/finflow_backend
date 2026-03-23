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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Optional: Budget can be for a specific category OR a whole group
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('cascade');

            $table->decimal('amount', 15, 2);

            // Period of the budget (e.g., monthly, weekly, yearly)
            $table->string('period')->default('monthly');

            // Month and Year for this budget (e.g., '2026-03-01')
            $table->date('start_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
