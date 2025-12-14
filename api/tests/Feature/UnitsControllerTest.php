<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_all_units(): void
    {
        $user = User::factory()->create();
        Unit::factory()->count(3)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/units');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_unauthenticated_user_cannot_get_units(): void
    {
        $response = $this->getJson('/api/units');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_unit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/units', [
                'title' => 'Test Unit',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'created_by',
                'creator',
                'users',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'title' => 'Test Unit',
                'created_by' => $user->id,
            ]);

        $this->assertDatabaseHas('units', [
            'title' => 'Test Unit',
            'created_by' => $user->id,
        ]);
    }

    public function test_unit_creation_fails_without_title(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/units', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_create_unit(): void
    {
        $response = $this->postJson('/api/units', [
            'title' => 'Test Unit',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_unit(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/units/{$unit->id}", [
                'title' => 'Updated Unit Title',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $unit->id,
                'title' => 'Updated Unit Title',
            ]);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'title' => 'Updated Unit Title',
        ]);
    }

    public function test_unit_update_fails_without_title(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/units/{$unit->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_update_unit(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->putJson("/api/units/{$unit->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_delete_unit(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/units/{$unit->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Unit deleted successfully',
            ]);

        $this->assertDatabaseMissing('units', [
            'id' => $unit->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_unit(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->deleteJson("/api/units/{$unit->id}");

        $response->assertStatus(401);
    }

    public function test_deleting_nonexistent_unit_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/units/999999');

        $response->assertStatus(404);
    }
}
