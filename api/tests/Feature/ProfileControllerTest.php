<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Symbol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create(['balance' => 50000]);
        $btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
        Asset::create([
            'user_id' => $user->id,
            'symbol_id' => $btc->id,
            'amount' => 1.5,
            'locked_amount' => 0.5,
        ]);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'balance' => '50000.00000000',
                'assets' => [
                    [
                        'symbol' => 'BTC',
                        'amount' => '1.50000000',
                        'locked_amount' => '0.50000000',
                    ],
                ],
            ]);
    }

    public function test_profile_returns_empty_assets_for_new_user(): void
    {
        $user = User::factory()->create(['balance' => 10000]);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'balance' => '10000.00000000',
                'assets' => [],
            ]);
    }

    public function test_profile_returns_multiple_assets(): void
    {
        $user = User::factory()->create(['balance' => 25000]);
        $btc = Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
        $eth = Symbol::create(['code' => 'ETH', 'name' => 'Ethereum']);

        Asset::create(['user_id' => $user->id, 'symbol_id' => $btc->id, 'amount' => 2.0, 'locked_amount' => 0]);
        Asset::create(['user_id' => $user->id, 'symbol_id' => $eth->id, 'amount' => 10.0, 'locked_amount' => 1.0]);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'assets');
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }
}
