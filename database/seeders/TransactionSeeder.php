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

        $foodCat = Category::where('name', 'Їжа')->first();
        $transportCat = Category::where('name', 'Транспорт')->first();
        $salaryCat = Category::where('name', 'Зарплата')->first();

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $foodCat->id,
            'amount' => 500.50,
            'description' => 'Вечеря в Сільпо',
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $transportCat->id,
            'amount' => 15.00,
            'description' => 'Метро',
            'type' => 'expense',
            'transaction_date' => now()->subDay(), // Вчора
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $salaryCat->id,
            'amount' => 25000.00,
            'description' => 'Основна зарплата',
            'type' => 'income',
            'transaction_date' => now(),
        ]);
    }
}
