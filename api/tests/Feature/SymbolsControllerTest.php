<?php

namespace Tests\Feature;

use App\Models\Symbol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SymbolsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_symbols(): void
    {
        Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);
        Symbol::create(['code' => 'ETH', 'name' => 'Ethereum']);

        $response = $this->getJson('/api/symbols');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'BTC')
            ->assertJsonPath('data.0.name', 'Bitcoin')
            ->assertJsonPath('data.1.code', 'ETH')
            ->assertJsonPath('data.1.name', 'Ethereum');
    }

    public function test_returns_empty_array_when_no_symbols(): void
    {
        $response = $this->getJson('/api/symbols');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_symbols_endpoint_is_public(): void
    {
        Symbol::create(['code' => 'BTC', 'name' => 'Bitcoin']);

        $response = $this->getJson('/api/symbols');

        $response->assertStatus(200);
    }
}
