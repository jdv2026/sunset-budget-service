<?php

namespace Tests\Feature;

use App\Models\BudgetActiveCategory;
use App\Services\JwksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetActiveCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const BASE    = '/api/web/budget-active-categories';
    private const STORE   = '/api/web/store/budget-active-categories';
    private const USER_A  = 'user-a';
    private const USER_B  = 'user-b';

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
    // GET /web/budget-active-categories
    // -------------------------------------------------------------------------

    public function test_index_returns_empty_for_new_user(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('payload'));
    }

    public function test_index_returns_only_own_categories(): void
    {
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Food']);
        $this->createCategory(['user_id' => self::USER_B, 'name' => 'Rent']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload'));
        $this->assertEquals('Food', $response->json('payload.0.name'));
    }

    public function test_index_returns_categories_sorted_by_name(): void
    {
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Zebra']);
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Apple']);
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Mango']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $names = array_column($response->json('payload'), 'name');
        $this->assertEquals(['Apple', 'Mango', 'Zebra'], $names);
    }

    // -------------------------------------------------------------------------
    // GET /web/budget-active-categories/wallets  (income type)
    // -------------------------------------------------------------------------

    public function test_for_wallets_returns_only_income_categories(): void
    {
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Salary',  'type' => 'income']);
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Savings', 'type' => 'goals']);
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Bills',   'type' => 'expense']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '/wallets');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload'));
        $this->assertEquals('Salary', $response->json('payload.0.name'));
    }

    // -------------------------------------------------------------------------
    // GET /web/budget-active-categories/bills  (expense type)
    // -------------------------------------------------------------------------

    public function test_for_bills_returns_only_expense_categories(): void
    {
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Rent',   'type' => 'expense']);
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Salary', 'type' => 'income']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '/bills');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload'));
        $this->assertEquals('Rent', $response->json('payload.0.name'));
    }

    // -------------------------------------------------------------------------
    // GET /web/budget-active-categories/goals  (goals type)
    // -------------------------------------------------------------------------

    public function test_for_goals_returns_only_goals_categories(): void
    {
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Vacation', 'type' => 'goals']);
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Salary',   'type' => 'income']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '/goals');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload'));
        $this->assertEquals('Vacation', $response->json('payload.0.name'));
    }

    // -------------------------------------------------------------------------
    // POST /web/store/budget-active-categories
    // -------------------------------------------------------------------------

    public function test_store_creates_category(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'  => 'Groceries',
            'icon'  => 'shopping_cart',
            'color' => '#ff0000',
            'type'  => 'expense',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budget_active_categories', [
            'user_id' => self::USER_A,
            'name'    => 'Groceries',
            'type'    => 'expense',
        ]);
    }

    public function test_store_accepts_optional_description(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Groceries',
            'icon'        => 'shopping_cart',
            'color'       => '#ff0000',
            'type'        => 'expense',
            'description' => 'Weekly grocery shopping',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('Weekly grocery shopping', $response->json('payload.description'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'icon', 'color', 'type']);
    }

    public function test_store_validates_type_must_be_valid(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'  => 'Groceries',
            'icon'  => 'shopping_cart',
            'color' => '#ff0000',
            'type'  => 'invalid-type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_validates_unique_name(): void
    {
        $this->createCategory(['user_id' => self::USER_A, 'name' => 'Groceries']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'  => 'Groceries',
            'icon'  => 'shopping_cart',
            'color' => '#ff0000',
            'type'  => 'expense',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_name_min_length(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'  => 'Ab',
            'icon'  => 'shopping_cart',
            'color' => '#ff0000',
            'type'  => 'expense',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // PUT /web/budget-active-categories/{id}
    // -------------------------------------------------------------------------

    public function test_update_modifies_category(): void
    {
        $category = $this->createCategory(['user_id' => self::USER_A, 'name' => 'OldName']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$category->id}", [
            'name'  => 'NewName',
            'icon'  => 'home',
            'color' => '#00ff00',
            'type'  => 'income',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_categories', [
            'id'   => $category->id,
            'name' => 'NewName',
            'type' => 'income',
        ]);
    }

    public function test_update_returns_404_for_other_user_category(): void
    {
        $category = $this->createCategory(['user_id' => self::USER_B, 'name' => 'Rent']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$category->id}", [
            'name'  => 'Hacked',
            'icon'  => 'home',
            'color' => '#00ff00',
            'type'  => 'expense',
        ]);

        $response->assertStatus(404);
    }

    public function test_update_allows_same_name_on_same_record(): void
    {
        $category = $this->createCategory(['user_id' => self::USER_A, 'name' => 'Groceries']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$category->id}", [
            'name'  => 'Groceries',
            'icon'  => 'home',
            'color' => '#00ff00',
            'type'  => 'expense',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_validates_required_fields(): void
    {
        $category = $this->createCategory(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$category->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'icon', 'color', 'type']);
    }

    // -------------------------------------------------------------------------
    // DELETE /web/budget-active-categories/{id}
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_category(): void
    {
        $category = $this->createCategory(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$category->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('budget_active_categories', ['id' => $category->id]);
    }

    public function test_destroy_returns_404_for_missing_category(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . '/99999');

        $response->assertStatus(404);
    }

    public function test_destroy_cannot_delete_other_user_category(): void
    {
        $category = $this->createCategory(['user_id' => self::USER_B]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$category->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('budget_active_categories', ['id' => $category->id]);
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

    private function createCategory(array $overrides = []): BudgetActiveCategory
    {
        return BudgetActiveCategory::create(array_merge([
            'user_id' => self::USER_A,
            'name'    => 'Default',
            'icon'    => 'home',
            'color'   => '#000000',
            'type'    => 'expense',
        ], $overrides));
    }
}
