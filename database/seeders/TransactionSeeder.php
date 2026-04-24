<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Знаходимо нашого тестового юзера
        $user = User::where('email', 'alex@gmail.com')->first();

        if (!$user) {
            $this->command->warn('User Alex Admin not found. Please run UserSeeder first.');
            return;
        }

        // 2. Підтягуємо його згенеровані обсервером дані
        $accounts = $user->accounts;
        $categories = $user->categories;
        $generalGroup = $user->groups()->where('name', 'General')->first();

        if ($accounts->isEmpty() || $categories->isEmpty() || !$generalGroup) {
            $this->command->warn('Default accounts, groups or categories are missing for this user.');
            return;
        }

        $transactions = [];
        $now = Carbon::now();

        // 3. Генеруємо 50 транзакцій
        for ($i = 0; $i < 50; $i++) {
            // Зробимо 80% витрат і 20% доходів, щоб графіки були реалістичнішими
            $isExpense = fake()->boolean(80);
            $type = $isExpense ? 'expense' : 'income';

            $account = $accounts->random();
            $category = $categories->random();

            $transactions[] = [
                'user_id'          => $user->id,
                'account_id'       => $account->id,
                'to_account_id'    => null, // Це для transfer
                'group_id'         => $generalGroup->id,
                'category_id'      => $category->id,
                'planned_transaction_id' => null,
                'title'            => fake()->sentence(3),
                'amount'           => fake()->randomFloat(2, 50, 5000), // Від 50 до 5000 грн
                'type'             => $type,
                'description'      => fake()->optional(0.5)->sentence(), // 50% шанс на наявність опису
                'transaction_date' => $now->copy()->subDays(rand(0, 60))->format('Y-m-d H:i:s'),
                'is_favorite'      => fake()->boolean(10), // 10% шанс, що в обраному
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        // 4. Масова вставка для швидкості (bulk insert)
        Transaction::insert($transactions);

        $this->command->info('Successfully generated 50 transactions for Alex Admin!');
    }
}
