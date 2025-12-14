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
                'amount' => $faker->randomFloat(8, 0.5, 5.0),
                'locked_amount' => $faker->randomFloat(8, 0, 0.5),
            ]);
            Asset::create([
                'user_id' => $user->id,
                'symbol_id' => $eth->id,
                'amount' => $faker->randomFloat(8, 5.0, 50.0),
                'locked_amount' => $faker->randomFloat(8, 0, 5.0),
            ]);
        }

        // Open BTC orders - sells (asks)
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $btc->id,
                'side' => 'sell',
                'price' => $faker->randomFloat(2, 97000, 99000),
                'amount' => $faker->randomFloat(8, 0.1, 0.8),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Open BTC orders - buys (bids)
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $btc->id,
                'side' => 'buy',
                'price' => $faker->randomFloat(2, 93000, 96000),
                'amount' => $faker->randomFloat(8, 0.1, 0.8),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Open ETH orders - sells
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $eth->id,
                'side' => 'sell',
                'price' => $faker->randomFloat(2, 3700, 4000),
                'amount' => $faker->randomFloat(8, 1.0, 10.0),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Open ETH orders - buys
        foreach ($users as $user) {
            Order::create([
                'user_id' => $user->id,
                'symbol_id' => $eth->id,
                'side' => 'buy',
                'price' => $faker->randomFloat(2, 3300, 3600),
                'amount' => $faker->randomFloat(8, 1.0, 10.0),
                'status' => Order::STATUS_OPEN,
            ]);
        }

        // Filled BTC orders (historical trades)
        for ($i = 0; $i < 15; $i++) {
            Order::create([
                'user_id' => $faker->randomElement($users)->id,
                'symbol_id' => $btc->id,
                'side' => $faker->randomElement(['buy', 'sell']),
                'price' => $faker->randomFloat(2, 90000, 98000),
                'amount' => $faker->randomFloat(8, 0.01, 0.5),
                'status' => Order::STATUS_FILLED,
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
            ]);
        }

        // Filled ETH orders (historical trades)
        for ($i = 0; $i < 15; $i++) {
            Order::create([
                'user_id' => $faker->randomElement($users)->id,
                'symbol_id' => $eth->id,
                'side' => $faker->randomElement(['buy', 'sell']),
                'price' => $faker->randomFloat(2, 3200, 3900),
                'amount' => $faker->randomFloat(8, 0.5, 8.0),
                'status' => Order::STATUS_FILLED,
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
            ]);
        }

        // Cancelled orders
        for ($i = 0; $i < 5; $i++) {
            Order::create([
                'user_id' => $faker->randomElement($users)->id,
                'symbol_id' => $faker->randomElement([$btc, $eth])->id,
                'side' => $faker->randomElement(['buy', 'sell']),
                'price' => $faker->randomFloat(2, 3000, 100000),
                'amount' => $faker->randomFloat(8, 0.1, 5.0),
                'status' => Order::STATUS_CANCELLED,
                'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
            ]);
        }
    }
}
