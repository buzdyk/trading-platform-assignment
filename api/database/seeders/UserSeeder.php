<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Symbol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create symbols
        $btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
        $eth = Symbol::create(['code' => 'ETH', 'name' => 'Ethereum']);

        $john = User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => Hash::make('password'),
            'balance' => 100000.00,
        ]);

        $jane = User::create([
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'password' => Hash::make('password'),
            'balance' => 100000.00,
        ]);

        // Give John some BTC
        Asset::create([
            'user_id' => $john->id,
            'symbol_id' => $btc->id,
            'amount' => 1.0,
            'locked_amount' => 0,
        ]);

        // Give Jane some ETH
        Asset::create([
            'user_id' => $jane->id,
            'symbol_id' => $eth->id,
            'amount' => 10.0,
            'locked_amount' => 0,
        ]);
    }
}
