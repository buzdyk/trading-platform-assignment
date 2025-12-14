<?php

namespace Tests\Unit;

use App\Actions\MatchOrder;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Symbol;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchOrderTest extends TestCase
{
    use RefreshDatabase;

    private Symbol $btc;

    private MatchOrder $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
        $this->action = new MatchOrder;
    }

    public function test_matches_buy_order_with_valid_sell_order(): void
    {
        $seller = User::factory()->create(['balance' => '0']);
        $buyer = User::factory()->create(['balance' => '100000', 'locked_balance' => '50000']);

        Asset::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'amount' => '1',
            'locked_amount' => '1',
        ]);

        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $trade = ($this->action)($buyOrder);

        $this->assertNotNull($trade);
        $this->assertEquals($buyOrder->id, $trade->buy_order_id);
        $this->assertEquals($sellOrder->id, $trade->sell_order_id);
        $this->assertEquals('50000.00000000', $trade->total);

        // Check commission (1.5% of 1 BTC = 0.015 BTC)
        $this->assertEquals('0.01500000', $trade->commission);

        // Check orders are filled
        $buyOrder->refresh();
        $sellOrder->refresh();
        $this->assertEquals(Order::STATUS_FILLED, $buyOrder->status);
        $this->assertEquals(Order::STATUS_FILLED, $sellOrder->status);
    }

    public function test_buyer_receives_assets_minus_commission(): void
    {
        $seller = User::factory()->create(['balance' => '0']);
        $buyer = User::factory()->create(['balance' => '100000', 'locked_balance' => '50000']);

        Asset::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'amount' => '1',
            'locked_amount' => '1',
        ]);

        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        ($this->action)($buyOrder);

        // Buyer should receive 1 - 0.015 = 0.985 BTC
        $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol_id', $this->btc->id)->first();
        $this->assertEquals('0.98500000', $buyerAsset->amount);
    }

    public function test_seller_receives_full_usd(): void
    {
        $seller = User::factory()->create(['balance' => '0']);
        $buyer = User::factory()->create(['balance' => '100000', 'locked_balance' => '50000']);

        Asset::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'amount' => '1',
            'locked_amount' => '1',
        ]);

        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        ($this->action)($buyOrder);

        $seller->refresh();
        $this->assertEquals('50000.00000000', $seller->balance);
    }

    public function test_no_match_when_no_counter_order(): void
    {
        $buyer = User::factory()->create(['balance' => '100000', 'locked_balance' => '50000']);

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $trade = ($this->action)($buyOrder);

        $this->assertNull($trade);
        $buyOrder->refresh();
        $this->assertEquals(Order::STATUS_OPEN, $buyOrder->status);
    }

    public function test_no_match_when_sell_price_too_high(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create(['balance' => '100000', 'locked_balance' => '50000']);

        Asset::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'amount' => '1',
            'locked_amount' => '1',
        ]);

        Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => '60000', // Higher than buy price
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $trade = ($this->action)($buyOrder);

        $this->assertNull($trade);
    }

    public function test_buy_taker_gets_maker_sell_price(): void
    {
        $seller = User::factory()->create(['balance' => '0']);
        $buyer = User::factory()->create(['balance' => '100000', 'locked_balance' => '55000']);

        Asset::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'amount' => '1',
            'locked_amount' => '1',
        ]);

        // Maker: sell order at 50k (already on book)
        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        // Taker: buy order at 55k (willing to pay more)
        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '55000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $trade = ($this->action)($buyOrder);

        // Trade executes at maker's (sell) price - buyer gets better deal
        $this->assertEquals('50000.00000000', $trade->price);
        $this->assertEquals('50000.00000000', $trade->total);

        // Verify seller receives the trade price
        $seller->refresh();
        $this->assertEquals('50000.00000000', $seller->balance);
    }

    public function test_sell_taker_gets_maker_buy_price(): void
    {
        $buyer = User::factory()->create(['balance' => '100000', 'locked_balance' => '55000']);
        $seller = User::factory()->create(['balance' => '0']);

        Asset::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'amount' => '1',
            'locked_amount' => '1',
        ]);

        // Maker: buy order at 55k (already on book, willing to pay more)
        $buyOrder = Order::create([
            'user_id' => $buyer->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '55000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        // Taker: sell order at 50k (asking less)
        $sellOrder = Order::create([
            'user_id' => $seller->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $trade = ($this->action)($sellOrder);

        // Trade executes at maker's (buy) price - seller gets better deal
        $this->assertEquals('55000.00000000', $trade->price);
        $this->assertEquals('55000.00000000', $trade->total);

        // Verify seller receives the higher price
        $seller->refresh();
        $this->assertEquals('55000.00000000', $seller->balance);
    }

    public function test_does_not_match_own_orders(): void
    {
        $user = User::factory()->create(['balance' => '100000', 'locked_balance' => '50000']);

        Asset::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'amount' => '2',
            'locked_amount' => '1',
        ]);

        Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_SELL,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $buyOrder = Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => '50000',
            'amount' => '1',
            'status' => Order::STATUS_OPEN,
        ]);

        $trade = ($this->action)($buyOrder);

        $this->assertNull($trade);
    }
}
