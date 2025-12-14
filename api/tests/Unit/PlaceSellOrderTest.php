<?php

namespace Tests\Unit;

use App\Actions\MatchOrder;
use App\Actions\PlaceSellOrder;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Symbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PlaceSellOrderTest extends TestCase
{
    use RefreshDatabase;

    private Symbol $btc;

    private PlaceSellOrder $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);

        $matchOrder = Mockery::mock(MatchOrder::class);
        $matchOrder->shouldReceive('__invoke')->andReturnNull();

        $this->action = new PlaceSellOrder($matchOrder);
    }

    public function test_places_sell_order_with_sufficient_assets(): void
    {
        $user = User::factory()->create();
        Asset::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'amount' => '5',
            'locked_amount' => '0',
        ]);

        $order = ($this->action)($user, $this->btc->id, '50000', '2');

        $this->assertEquals(Order::SIDE_SELL, $order->side);
        $this->assertEquals('50000.00000000', $order->price);
        $this->assertEquals('2.00000000', $order->amount);
        $this->assertEquals(Order::STATUS_OPEN, $order->status);

        $asset = Asset::where('user_id', $user->id)->where('symbol_id', $this->btc->id)->first();
        $this->assertEquals('2.00000000', $asset->locked_amount);
    }

    public function test_fails_with_no_asset(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No asset found for this symbol');

        ($this->action)($user, $this->btc->id, '50000', '1');
    }

    public function test_fails_with_insufficient_assets(): void
    {
        $user = User::factory()->create();
        Asset::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'amount' => '1',
            'locked_amount' => '0',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient asset balance');

        ($this->action)($user, $this->btc->id, '50000', '5');
    }

    public function test_fails_when_available_amount_insufficient_due_to_locked(): void
    {
        $user = User::factory()->create();
        Asset::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'amount' => '5',
            'locked_amount' => '4',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient asset balance');

        ($this->action)($user, $this->btc->id, '50000', '2');
    }

    public function test_locks_correct_amount(): void
    {
        $user = User::factory()->create();
        Asset::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'amount' => '10',
            'locked_amount' => '2',
        ]);

        ($this->action)($user, $this->btc->id, '50000', '3');

        $asset = Asset::where('user_id', $user->id)->where('symbol_id', $this->btc->id)->first();
        $this->assertEquals('5.00000000', $asset->locked_amount); // 2 + 3
    }
}
