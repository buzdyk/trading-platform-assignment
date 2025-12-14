<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Symbol;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradesControllerTest extends TestCase
{
    use RefreshDatabase;

    private Symbol $btc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
    }

    public function test_authenticated_user_can_get_trades_as_buyer(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'price' => 50000,
            'amount' => 0.5,
            'total' => 25000,
            'commission' => 0.0005,
        ]);

        $response = $this->actingAs($buyer)->getJson('/api/trades');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.symbol', 'BTC')
            ->assertJsonPath('data.0.side', 'buy');
    }

    public function test_authenticated_user_can_get_trades_as_seller(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'price' => 50000,
            'amount' => 0.5,
            'total' => 25000,
            'commission' => 0.0005,
        ]);

        $response = $this->actingAs($seller)->getJson('/api/trades');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.symbol', 'BTC')
            ->assertJsonPath('data.0.side', 'sell');
    }

    public function test_user_only_sees_own_trades(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Trade between user2 and user3 (user1 not involved)
        $buyOrder = Order::create([
            'user_id' => $user2->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        $sellOrder = Order::create([
            'user_id' => $user3->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $user2->id,
            'seller_id' => $user3->id,
            'symbol_id' => $this->btc->id,
            'price' => 50000,
            'amount' => 0.5,
            'total' => 25000,
            'commission' => 0.0005,
        ]);

        // user1 should see no trades
        $response = $this->actingAs($user1)->getJson('/api/trades');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_trades_ordered_by_created_at_desc(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        // Create older trade first
        $buyOrder1 = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);
        $sellOrder1 = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);
        $trade1 = Trade::create([
            'buy_order_id' => $buyOrder1->id,
            'sell_order_id' => $sellOrder1->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'price' => 50000,
            'amount' => 0.5,
            'total' => 25000,
            'commission' => 0.0005,
        ]);
        $trade1->forceFill(['created_at' => now()->subHour()])->save();

        // Create newer trade
        $buyOrder2 = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 51000,
            'amount' => 0.3,
            'status' => Order::STATUS_FILLED,
        ]);
        $sellOrder2 = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => 51000,
            'amount' => 0.3,
            'status' => Order::STATUS_FILLED,
        ]);
        $trade2 = Trade::create([
            'buy_order_id' => $buyOrder2->id,
            'sell_order_id' => $sellOrder2->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'price' => 51000,
            'amount' => 0.3,
            'total' => 15300,
            'commission' => 0.0003,
        ]);

        $response = $this->actingAs($buyer)->getJson('/api/trades');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $trade2->id)
            ->assertJsonPath('data.1.id', $trade1->id);
    }

    public function test_trade_response_includes_correct_fields(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'price' => 50000,
            'amount' => 0.5,
            'total' => 25000,
            'commission' => 0.0005,
        ]);

        $response = $this->actingAs($buyer)->getJson('/api/trades');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'symbol',
                        'side',
                        'price',
                        'amount',
                        'total',
                        'commission',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_buyer_sees_commission_seller_sees_zero(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'price' => 50000,
            'amount' => 0.5,
            'total' => 25000,
            'commission' => 0.0005,
        ]);

        // Buyer sees actual commission
        $buyerResponse = $this->actingAs($buyer)->getJson('/api/trades');
        $buyerResponse->assertJsonPath('data.0.commission', '0.00050000');

        // Seller sees zero commission
        $sellerResponse = $this->actingAs($seller)->getJson('/api/trades');
        $sellerResponse->assertJsonPath('data.0.commission', '0');
    }

    public function test_unauthenticated_user_cannot_access_trades(): void
    {
        $this->getJson('/api/trades')->assertStatus(401);
    }
}
