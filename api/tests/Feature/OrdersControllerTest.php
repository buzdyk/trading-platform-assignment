<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\Symbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrdersControllerTest extends TestCase
{
    use RefreshDatabase;

    private Symbol $btc;

    private Symbol $eth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
        $this->eth = Symbol::create(['code' => 'ETH', 'name' => 'Ethereum']);
    }

    public function test_can_get_open_orders(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.symbol', 'BTC')
            ->assertJsonPath('data.0.side', 'buy');
    }

    public function test_can_filter_orders_by_symbol(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_OPEN,
        ]);
        Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->eth->id,
            'side' => Order::SIDE_SELL,
            'price' => 3000,
            'amount' => 2.0,
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->actingAs($user)->getJson('/api/orders?symbol_id='.$this->btc->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.symbol', 'BTC');
    }

    public function test_cancelled_orders_not_in_orderbook(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_can_create_buy_order(): void
    {
        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol_id' => $this->btc->id,
            'side' => 'buy',
            'price' => 50000,
            'amount' => 0.5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.symbol', 'BTC')
            ->assertJsonPath('order.side', 'buy')
            ->assertJsonPath('order.status', 'open');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => 'buy',
        ]);
    }

    public function test_can_create_sell_order(): void
    {
        $user = User::factory()->create();
        \App\Models\Asset::create([
            'user_id' => $user->id,
            'symbol_id' => $this->eth->id,
            'amount' => 10.0,
            'locked_amount' => 0,
        ]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol_id' => $this->eth->id,
            'side' => 'sell',
            'price' => 3000,
            'amount' => 5.0,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.symbol', 'ETH')
            ->assertJsonPath('order.side', 'sell');
    }

    public function test_create_order_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol_id', 'side', 'price', 'amount']);
    }

    public function test_create_order_validates_symbol_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol_id' => 9999,
            'side' => 'buy',
            'price' => 50000,
            'amount' => 0.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol_id']);
    }

    public function test_create_order_validates_side(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol_id' => $this->btc->id,
            'side' => 'invalid',
            'price' => 50000,
            'amount' => 0.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['side']);
    }

    public function test_create_order_validates_positive_price(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol_id' => $this->btc->id,
            'side' => 'buy',
            'price' => -100,
            'amount' => 0.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_can_cancel_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('order.status', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CANCELLED,
        ]);
    }

    public function test_cannot_cancel_other_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create([
            'user_id' => $owner->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->actingAs($other)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_cannot_cancel_already_cancelled_order(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_cannot_cancel_filled_order(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_FILLED,
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_orders(): void
    {
        $this->getJson('/api/orders')->assertStatus(401);
        $this->postJson('/api/orders')->assertStatus(401);
        $this->postJson('/api/orders/1/cancel')->assertStatus(401);
    }

    public function test_creating_order_broadcasts_event(): void
    {
        Event::fake([OrderUpdated::class]);

        $user = User::factory()->create(['balance' => 100000]);

        $this->actingAs($user)->postJson('/api/orders', [
            'symbol_id' => $this->btc->id,
            'side' => 'buy',
            'price' => 50000,
            'amount' => 0.5,
        ]);

        Event::assertDispatched(OrderUpdated::class, function (OrderUpdated $event) use ($user) {
            return $event->order->user_id === $user->id
                && $event->order->symbol_id === $this->btc->id
                && $event->order->status === Order::STATUS_OPEN;
        });
    }

    public function test_cancelling_order_broadcasts_event(): void
    {
        Event::fake([OrderUpdated::class]);

        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'symbol_id' => $this->btc->id,
            'side' => Order::SIDE_BUY,
            'price' => 50000,
            'amount' => 0.5,
            'status' => Order::STATUS_OPEN,
        ]);

        $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        Event::assertDispatched(OrderUpdated::class, function (OrderUpdated $event) use ($order) {
            return $event->order->id === $order->id
                && $event->order->status === Order::STATUS_CANCELLED;
        });
    }

    public function test_order_broadcast_contains_correct_data(): void
    {
        Event::fake([OrderUpdated::class]);

        $user = User::factory()->create(['balance' => 100000]);

        $this->actingAs($user)->postJson('/api/orders', [
            'symbol_id' => $this->btc->id,
            'side' => 'buy',
            'price' => 50000,
            'amount' => 0.5,
        ]);

        Event::assertDispatched(OrderUpdated::class, function (OrderUpdated $event) {
            $payload = $event->broadcastWith();

            return isset($payload['order']['id'])
                && isset($payload['order']['symbol'])
                && isset($payload['order']['side'])
                && isset($payload['order']['price'])
                && isset($payload['order']['amount'])
                && isset($payload['order']['status'])
                && $payload['order']['symbol'] === 'BTC'
                && $payload['order']['side'] === 'buy'
                && $payload['order']['status'] === 'open';
        });
    }
}
