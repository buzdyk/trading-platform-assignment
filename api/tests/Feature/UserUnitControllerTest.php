<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUnitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_user_to_unit(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        $response = $this->actingAs($authUser, 'sanctum')
            ->postJson('/api/user-units', [
                'user_id' => $user->id,
                'unit_id' => $unit->id,
                'role' => 'member',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User added to unit successfully',
                'data' => [
                    'user_id' => $user->id,
                    'unit_id' => $unit->id,
                    'role' => 'member',
                ],
            ]);

        $this->assertDatabaseHas('user_unit', [
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'role' => 'member',
        ]);
    }

    public function test_adding_duplicate_user_to_unit_fails(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        // Add user first time
        $unit->users()->attach($user->id, ['role' => 'member']);

        $response = $this->actingAs($authUser, 'sanctum')
            ->postJson('/api/user-units', [
                'user_id' => $user->id,
                'unit_id' => $unit->id,
                'role' => 'admin',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'User is already associated with this unit',
            ]);
    }

    public function test_adding_user_to_unit_fails_with_invalid_user_id(): void
    {
        $authUser = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        $response = $this->actingAs($authUser, 'sanctum')
            ->postJson('/api/user-units', [
                'user_id' => 99999,
                'unit_id' => $unit->id,
                'role' => 'member',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_adding_user_to_unit_fails_with_invalid_unit_id(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($authUser, 'sanctum')
            ->postJson('/api/user-units', [
                'user_id' => $user->id,
                'unit_id' => 99999,
                'role' => 'member',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unit_id']);
    }

    public function test_adding_user_to_unit_requires_role(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        $response = $this->actingAs($authUser, 'sanctum')
            ->postJson('/api/user-units', [
                'user_id' => $user->id,
                'unit_id' => $unit->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_unauthenticated_user_cannot_add_user_to_unit(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();

        $response = $this->postJson('/api/user-units', [
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'role' => 'member',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_user_role(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        // Add user to unit first
        $unit->users()->attach($user->id, ['role' => 'member']);

        $response = $this->actingAs($authUser, 'sanctum')
            ->putJson("/api/user-units/{$user->id}/{$unit->id}", [
                'role' => 'admin',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User role updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'unit_id' => $unit->id,
                    'role' => 'admin',
                ],
            ]);

        $this->assertDatabaseHas('user_unit', [
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'role' => 'admin',
        ]);
    }

    public function test_updating_nonexistent_user_unit_relation_fails(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        $response = $this->actingAs($authUser, 'sanctum')
            ->putJson("/api/user-units/{$user->id}/{$unit->id}", [
                'role' => 'admin',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User is not associated with this unit',
            ]);
    }

    public function test_updating_user_role_requires_role_field(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        $unit->users()->attach($user->id, ['role' => 'member']);

        $response = $this->actingAs($authUser, 'sanctum')
            ->putJson("/api/user-units/{$user->id}/{$unit->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_unauthenticated_user_cannot_update_user_role(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();

        $response = $this->putJson("/api/user-units/{$user->id}/{$unit->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_remove_user_from_unit(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        // Add user to unit first
        $unit->users()->attach($user->id, ['role' => 'member']);

        $response = $this->actingAs($authUser, 'sanctum')
            ->deleteJson("/api/user-units/{$user->id}/{$unit->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User removed from unit successfully',
            ]);

        $this->assertDatabaseMissing('user_unit', [
            'user_id' => $user->id,
            'unit_id' => $unit->id,
        ]);
    }

    public function test_removing_nonexistent_user_unit_relation_fails(): void
    {
        $authUser = User::factory()->create();
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['created_by' => $authUser->id]);

        $response = $this->actingAs($authUser, 'sanctum')
            ->deleteJson("/api/user-units/{$user->id}/{$unit->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User is not associated with this unit',
            ]);
    }

    public function test_unauthenticated_user_cannot_remove_user_from_unit(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create();

        $response = $this->deleteJson("/api/user-units/{$user->id}/{$unit->id}");

        $response->assertStatus(401);
    }
}
