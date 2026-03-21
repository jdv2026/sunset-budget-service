<?php

namespace Tests\Feature;

use App\Models\BudgetActiveCategory;
use App\Models\BudgetActiveGoal;
use App\Services\JwksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetActiveGoalTest extends TestCase
{
    use RefreshDatabase;

    private const BASE   = '/api/web/budget-active-goals';
    private const STORE  = '/api/web/store/budget-active-goals';
    private const USER_A = 'user-a';
    private const USER_B = 'user-b';

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------

    public function test_requires_authentication(): void
    {
        $response = $this->withToken('')->getJson(self::BASE);

        $response->assertStatus(401);
    }

    public function test_returns_401_for_invalid_token(): void
    {
        $response = $this->withToken('invalid-token')->getJson(self::BASE);

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // GET /web/budget-active-goals
    // -------------------------------------------------------------------------

    public function test_index_returns_empty_for_new_user(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('payload'));
    }

    public function test_index_returns_only_own_goals(): void
    {
        $this->createGoal(['user_id' => self::USER_A, 'name' => 'Trip']);
        $this->createGoal(['user_id' => self::USER_B, 'name' => 'Car']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload'));
        $this->assertEquals('Trip', $response->json('payload.0.name'));
    }

    public function test_index_returns_goals_ordered_by_created_at(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->createGoal(['user_id' => self::USER_A, 'name' => 'First',  'created_at' => now()->subDays(2)]);
        $this->createGoal(['user_id' => self::USER_A, 'name' => 'Second', 'created_at' => now()->subDay()]);
        $this->createGoal(['user_id' => self::USER_A, 'name' => 'Third',  'created_at' => now()]);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $names = array_column($response->json('payload'), 'name');
        $this->assertEquals(['First', 'Second', 'Third'], $names);
    }

    // -------------------------------------------------------------------------
    // POST /web/store/budget-active-goals
    // -------------------------------------------------------------------------

    public function test_store_creates_goal(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'New Car',
            'amount'      => 5000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budget_active_goals', [
            'user_id' => self::USER_A,
            'name'    => 'New Car',
            'amount'  => 5000,
            'saved'   => 0,
        ]);
    }

    public function test_store_denormalizes_category_fields(): void
    {
        $category = $this->createCategory(self::USER_A, [
            'name'  => 'Savings',
            'icon'  => 'savings',
            'color' => '#00ff00',
            'type'  => 'goals',
        ]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'New Car',
            'amount'      => 5000,
        ]);

        $response->assertStatus(201);
        $payload = $response->json('payload');
        $this->assertEquals('Savings', $payload['category_name']);
        $this->assertEquals('savings', $payload['category_icon']);
        $this->assertEquals('#00ff00', $payload['category_color']);
        $this->assertEquals('goals',   $payload['category_type']);
    }

    public function test_store_accepts_optional_fields(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'New Car',
            'amount'      => 5000,
            'description' => 'My dream car',
            'deadline'    => '2027-12-31',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('My dream car', $response->json('payload.description'));
        $this->assertEquals('2027-12-31',   $response->json('payload.deadline'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'amount']);
    }

    public function test_store_rejects_non_goals_category(): void
    {
        $category = $this->createCategory(self::USER_A, ['type' => 'expense']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'New Car',
            'amount'      => 5000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_store_validates_unique_name_per_user(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->createGoal(['user_id' => self::USER_A, 'name' => 'New Car']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'New Car',
            'amount'      => 5000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_allows_same_name_for_different_users(): void
    {
        $this->createGoal(['user_id' => self::USER_B, 'name' => 'New Car']);
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'New Car',
            'amount'      => 5000,
        ]);

        $response->assertStatus(201);
    }

    public function test_store_validates_amount_is_non_negative(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'New Car',
            'amount'      => -1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    // -------------------------------------------------------------------------
    // PUT /web/budget-active-goals/{id}
    // -------------------------------------------------------------------------

    public function test_update_modifies_goal(): void
    {
        $category = $this->createCategory(self::USER_A);
        $goal     = $this->createGoal(['user_id' => self::USER_A, 'name' => 'Old Name', 'amount' => 1000]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$goal->id}", [
            'category_id' => $category->id,
            'name'        => 'New Name',
            'amount'      => 9999,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_goals', [
            'id'     => $goal->id,
            'name'   => 'New Name',
            'amount' => 9999,
        ]);
    }

    public function test_update_denormalizes_category_fields(): void
    {
        $category = $this->createCategory(self::USER_A, [
            'name'  => 'Renamed',
            'icon'  => 'new_icon',
            'color' => '#123456',
            'type'  => 'goals',
        ]);
        $goal = $this->createGoal(['user_id' => self::USER_A, 'name' => 'Trip']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$goal->id}", [
            'category_id' => $category->id,
            'name'        => 'Trip',
            'amount'      => 1000,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_goals', [
            'id'             => $goal->id,
            'category_name'  => 'Renamed',
            'category_icon'  => 'new_icon',
            'category_color' => '#123456',
        ]);
    }

    public function test_update_returns_404_for_other_user_goal(): void
    {
        $category = $this->createCategory(self::USER_A);
        $goal     = $this->createGoal(['user_id' => self::USER_B, 'name' => 'Trip']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$goal->id}", [
            'category_id' => $category->id,
            'name'        => 'Hacked',
            'amount'      => 1,
        ]);

        $response->assertStatus(404);
    }

    public function test_update_allows_same_name_on_same_record(): void
    {
        $category = $this->createCategory(self::USER_A);
        $goal     = $this->createGoal(['user_id' => self::USER_A, 'name' => 'Trip']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$goal->id}", [
            'category_id' => $category->id,
            'name'        => 'Trip',
            'amount'      => 2000,
        ]);

        $response->assertStatus(200);
    }

    public function test_update_validates_required_fields(): void
    {
        $goal = $this->createGoal(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$goal->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'amount']);
    }

    // -------------------------------------------------------------------------
    // DELETE /web/budget-active-goals/{id}
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_goal(): void
    {
        $goal = $this->createGoal(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$goal->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('budget_active_goals', ['id' => $goal->id]);
    }

    public function test_destroy_returns_404_for_missing_goal(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . '/99999');

        $response->assertStatus(404);
    }

    public function test_destroy_cannot_delete_other_user_goal(): void
    {
        $goal = $this->createGoal(['user_id' => self::USER_B]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$goal->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('budget_active_goals', ['id' => $goal->id]);
    }

    public function test_destroy_blocked_when_goal_has_saved_funds(): void
    {
        $goal = $this->createGoal(['user_id' => self::USER_A, 'saved' => 500]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$goal->id}");

        $response->assertStatus(500);
        $this->assertDatabaseHas('budget_active_goals', ['id' => $goal->id]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function mockJwks(string $userId): void
    {
        $this->mock(JwksService::class, function ($mock) use ($userId) {
            $mock->shouldReceive('verifyToken')
                ->andReturn((object) ['sub' => $userId, 'pre_auth' => false]);
        });
    }

    private function createCategory(string $userId, array $overrides = []): BudgetActiveCategory
    {
        return BudgetActiveCategory::create(array_merge([
            'user_id' => $userId,
            'name'    => 'Goals Cat',
            'icon'    => 'savings',
            'color'   => '#000000',
            'type'    => 'goals',
        ], $overrides));
    }

    private function createGoal(array $overrides = []): BudgetActiveGoal
    {
        return BudgetActiveGoal::create(array_merge([
            'user_id'        => self::USER_A,
            'name'           => 'Default Goal',
            'category_name'  => 'Goals Cat',
            'category_icon'  => 'savings',
            'category_color' => '#000000',
            'category_type'  => 'goals',
            'amount'         => 1000,
            'saved'          => 0,
        ], $overrides));
    }
}
