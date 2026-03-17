<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        // Fetch categories using the English names from our CategorySeeder
        $foodCat = Category::where('name', 'Food')->first();
        $petsCat = Category::where('name', 'Pets')->first();
        $familyCat = Category::where('name', 'Family')->first();
        $salaryCat = Category::where('name', 'Salary')->first();
        $investCat = Category::where('name', 'Investments')->first();

        /**
         * Food Category Transactions
         */
        $this->createTransaction($user, $foodCat, 'Silpo Supermarket', 500.50, 'expense');
        $this->createTransaction($user, $foodCat, 'Dinner at Restaurant', 1200.00, 'expense', true);

        /**
         * Pets Category Transactions
         */
        $this->createTransaction($user, $petsCat, 'Dog Food', 850.00, 'expense');
        $this->createTransaction($user, $petsCat, 'Vet Clinic Visit', 450.00, 'expense');

        /**
         * Family Category Transactions
         */
        $this->createTransaction($user, $familyCat, 'Movie Tickets', 300.00, 'expense');
        $this->createTransaction($user, $familyCat, 'Weekend Trip', 2500.00, 'expense', true);

        /**
         * Salary & Investments (Income/Savings)
         */
        $this->createTransaction($user, $salaryCat, 'Monthly Salary', 45000.00, 'income');
        $this->createTransaction($user, $salaryCat, 'Project Bonus', 10000.00, 'income');

        $this->createTransaction($user, $investCat, 'Stock Purchase', 5000.00, 'expense');
        $this->createTransaction($user, $investCat, 'Crypto Investment', 2000.00, 'expense');
    }

    /**
     * Helper method to create transactions with proper group_id mapping
     */
    private function createTransaction($user, $category, $title, $amount, $type, $isFavorite = false)
    {
        if (!$category) return;

        Transaction::create([
            'user_id'          => $user->id,
            'category_id'      => $category->id,
            'group_id'         => $category->group_id,
            'title'            => $title,
            'amount'           => $amount,
            'description'      => 'Test transaction for ' . $category->name,
            'type'             => $type,
            'is_favorite'      => $isFavorite,
            'transaction_date' => now()->subDays(rand(0, 10)),
        ]);
    }
}
