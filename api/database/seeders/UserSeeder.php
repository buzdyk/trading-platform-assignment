<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Symbol;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create symbols
        $btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
        $eth = Symbol::create(['code' => 'ETH', 'name' => 'Ethereum']);

        // Create users
        $john = User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => Hash::make('password'),
            'balance' => $faker->randomFloat(2, 50000, 150000),
        ]);

        $jane = User::create([
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'password' => Hash::make('password'),
            'balance' => $faker->randomFloat(2, 50000, 150000),
        ]);

        $bob = User::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
            'balance' => $faker->randomFloat(2, 50000, 150000),
        ]);

        $alice = User::create([
            'name' => 'Alice Wong',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
            'balance' => $faker->randomFloat(2, 50000, 150000),
        ]);

        $users = [$john, $jane, $bob, $alice];

        // Assets - all users have both BTC and ETH
        foreach ($users as $user) {
            Asset::create([
                'user_id' => $user->id,
                'symbol_id' => $btc->id,
                'amount' => $faker->randomFloat(8, 0.5, 2.0),
                'locked_amount' => $faker->randomFloat(8, 0, 0.5),
            ]);
            Asset::create([
                'user_id' => $user->id,
                'symbol_id' => $eth->id,
                'amount' => $faker->randomFloat(8, 10, 20.0),
                'locked_amount' => $faker->randomFloat(8, 0, 5.0),
            ]);
        }

        // Open BTC orders - sells (asks) ~$10k avg (0.05-0.15 BTC at ~$97k)
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $btc->id,
                'side' => 'sell',
                'price' => $faker->randomFloat(2, 97000, 99000),
                'amount' => $faker->randomFloat(8, 0.05, 0.15),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Open BTC orders - buys (bids) ~$10k avg
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $btc->id,
                'side' => 'buy',
                'price' => $faker->randomFloat(2, 93000, 96000),
                'amount' => $faker->randomFloat(8, 0.05, 0.15),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Open ETH orders - sells ~$10k avg (1.5-4 ETH at ~$3.7k)
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $eth->id,
                'side' => 'sell',
                'price' => $faker->randomFloat(2, 3700, 4000),
                'amount' => $faker->randomFloat(8, 1.5, 4.0),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Open ETH orders - buys ~$10k avg
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $eth->id,
                'side' => 'buy',
                'price' => $faker->randomFloat(2, 3300, 3600),
                'amount' => $faker->randomFloat(8, 1.5, 4.0),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Filled BTC orders (historical trades) ~$10k avg
        for ($i = 0; $i < 15; $i++) {
            Order::create([
                'user_id' => $faker->randomElement($users)->id,
                'symbol_id' => $btc->id,
                'side' => $faker->randomElement(['buy', 'sell']),
                'price' => $faker->randomFloat(2, 90000, 98000),
                'amount' => $faker->randomFloat(8, 0.05, 0.15),
                'status' => Order::STATUS_FILLED,
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
            ]);
        }

        // Filled ETH orders (historical trades) ~$10k avg
        for ($i = 0; $i < 15; $i++) {
            Order::create([
                'user_id' => $faker->randomElement($users)->id,
                'symbol_id' => $eth->id,
                'side' => $faker->randomElement(['buy', 'sell']),
                'price' => $faker->randomFloat(2, 3200, 3900),
                'amount' => $faker->randomFloat(8, 1.5, 4.0),
                'status' => Order::STATUS_FILLED,
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
            ]);
        }

        // Cancelled orders ~$10k avg
        for ($i = 0; $i < 5; $i++) {
            $symbol = $faker->randomElement([$btc, $eth]);
            $isBtc = $symbol->id === $btc->id;
            Order::create([
                'user_id' => $faker->randomElement($users)->id,
                'symbol_id' => $symbol->id,
                'side' => $faker->randomElement(['buy', 'sell']),
                'price' => $isBtc ? $faker->randomFloat(2, 93000, 99000) : $faker->randomFloat(2, 3300, 4000),
                'amount' => $isBtc ? $faker->randomFloat(8, 0.05, 0.15) : $faker->randomFloat(8, 1.5, 4.0),
                'status' => Order::STATUS_CANCELLED,
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
            ]);
        }
    }
}
