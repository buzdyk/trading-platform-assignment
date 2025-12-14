<?php

namespace Tests\Unit;

use App\Actions\MatchOrder;
use App\Actions\PlaceBuyOrder;
use App\Models\Order;
use App\Models\Symbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PlaceBuyOrderTest extends TestCase
{
    use RefreshDatabase;

    private Symbol $btc;

    private PlaceBuyOrder $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);

        $matchOrder = Mockery::mock(MatchOrder::class);
        $matchOrder->shouldReceive('__invoke')->andReturnNull();

        $this->action = new PlaceBuyOrder($matchOrder);
    }

    public function test_places_buy_order_with_sufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '100000', 'locked_balance' => '0']);

        $order = ($this->action)($user, $this->btc->id, '50000', '1');

        $this->assertEquals(Order::SIDE_BUY, $order->side);
        $this->assertEquals('50000.00000000', $order->price);
        $this->assertEquals('1.00000000', $order->amount);
        $this->assertEquals(Order::STATUS_OPEN, $order->status);

        $user->refresh();
        $this->assertEquals('50000.00000000', $user->locked_balance);
    }

    public function test_fails_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '10000', 'locked_balance' => '0']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient balance');

        ($this->action)($user, $this->btc->id, '50000', '1');
    }

    public function test_fails_when_available_balance_insufficient_due_to_locked(): void
    {
        $user = User::factory()->create(['balance' => '100000', 'locked_balance' => '60000']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient balance');

        ($this->action)($user, $this->btc->id, '50000', '1');
    }

    public function test_locks_correct_amount(): void
    {
        $user = User::factory()->create(['balance' => '100000', 'locked_balance' => '10000']);

        ($this->action)($user, $this->btc->id, '25000', '2');

        $user->refresh();
        $this->assertEquals('60000.00000000', $user->locked_balance); // 10000 + 50000
    }
}
