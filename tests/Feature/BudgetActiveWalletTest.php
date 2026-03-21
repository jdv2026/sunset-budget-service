<?php

namespace Tests\Feature;

use App\Models\BudgetActiveCategory;
use App\Models\BudgetActiveWallet;
use App\Services\JwksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetActiveWalletTest extends TestCase
{
    use RefreshDatabase;

    private const BASE   = '/api/web/budget-active-wallets';
    private const STORE  = '/api/web/store/budget-active-wallets';
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
    // GET /web/budget-active-wallets
    // -------------------------------------------------------------------------

    public function test_index_returns_empty_for_new_user(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('payload'));
    }

    public function test_index_returns_only_own_wallets(): void
    {
        $this->createWallet(['user_id' => self::USER_A, 'name' => 'Savings']);
        $this->createWallet(['user_id' => self::USER_B, 'name' => 'Checking']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload'));
        $this->assertEquals('Savings', $response->json('payload.0.name'));
    }

    public function test_index_returns_wallets_sorted_by_name(): void
    {
        $this->createWallet(['user_id' => self::USER_A, 'name' => 'Zakat']);
        $this->createWallet(['user_id' => self::USER_A, 'name' => 'Cash']);
        $this->createWallet(['user_id' => self::USER_A, 'name' => 'Main']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $names = array_column($response->json('payload'), 'name');
        $this->assertEquals(['Cash', 'Main', 'Zakat'], $names);
    }

    // -------------------------------------------------------------------------
    // POST /web/store/budget-active-wallets
    // -------------------------------------------------------------------------

    public function test_store_creates_wallet(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Main',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budget_active_wallets', [
            'user_id' => self::USER_A,
            'name'    => 'Main',
        ]);
    }

    public function test_store_defaults_amount_to_zero(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Main',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(0, $response->json('payload.amount'));
    }

    public function test_store_denormalizes_category_fields(): void
    {
        $category = $this->createCategory(self::USER_A, [
            'name'  => 'Salary',
            'icon'  => 'payments',
            'color' => '#00ccff',
            'type'  => 'income',
        ]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Main',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201);
        $payload = $response->json('payload');
        $this->assertEquals('Salary',   $payload['category_name']);
        $this->assertEquals('payments', $payload['category_icon']);
        $this->assertEquals('#00ccff',  $payload['category_color']);
        $this->assertEquals('income',   $payload['category_type']);
    }

    public function test_store_accepts_any_category_type(): void
    {
        $this->mockJwks(self::USER_A);

        foreach (['income', 'expense', 'goals'] as $type) {
            $category = $this->createCategory(self::USER_A, ['name' => "Cat {$type}", 'type' => $type]);

            $response = $this->withToken('valid-token')->postJson(self::STORE, [
                'name'        => "Wallet {$type}",
                'category_id' => $category->id,
            ]);

            $response->assertStatus(201);
        }
    }

    public function test_store_accepts_optional_description(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Main',
            'category_id' => $category->id,
            'description' => 'Primary wallet',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('Primary wallet', $response->json('payload.description'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id']);
    }

    public function test_store_validates_unique_name_per_user(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->createWallet(['user_id' => self::USER_A, 'name' => 'Main']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Main',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_allows_same_name_for_different_users(): void
    {
        $this->createWallet(['user_id' => self::USER_B, 'name' => 'Main']);
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Main',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201);
    }

    public function test_store_validates_category_exists(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'name'        => 'Main',
            'category_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    // -------------------------------------------------------------------------
    // PUT /web/budget-active-wallets/{id}
    // -------------------------------------------------------------------------

    public function test_update_modifies_wallet(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'name' => 'Old Name']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$wallet->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_wallets', [
            'id'   => $wallet->id,
            'name' => 'New Name',
        ]);
    }

    public function test_update_denormalizes_category_when_provided(): void
    {
        $wallet   = $this->createWallet(['user_id' => self::USER_A, 'name' => 'Main']);
        $category = $this->createCategory(self::USER_A, [
            'name'  => 'New Cat',
            'icon'  => 'new_icon',
            'color' => '#111111',
            'type'  => 'income',
        ]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$wallet->id}", [
            'name'        => 'Main',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_wallets', [
            'id'             => $wallet->id,
            'category_name'  => 'New Cat',
            'category_icon'  => 'new_icon',
            'category_color' => '#111111',
        ]);
    }

    public function test_update_preserves_category_when_no_category_id_provided(): void
    {
        $wallet = $this->createWallet([
            'user_id'        => self::USER_A,
            'name'           => 'Main',
            'category_name'  => 'Original Cat',
            'category_icon'  => 'original_icon',
            'category_color' => '#999999',
        ]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$wallet->id}", [
            'name' => 'Renamed',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_wallets', [
            'id'            => $wallet->id,
            'name'          => 'Renamed',
            'category_name' => 'Original Cat',
        ]);
    }

    public function test_update_returns_404_for_other_user_wallet(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_B, 'name' => 'Savings']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$wallet->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(404);
    }

    public function test_update_allows_same_name_on_same_record(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'name' => 'Main']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$wallet->id}", [
            'name' => 'Main',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_validates_name_is_required(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$wallet->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // DELETE /web/budget-active-wallets/{id}
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_wallet(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$wallet->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('budget_active_wallets', ['id' => $wallet->id]);
    }

    public function test_destroy_returns_404_for_missing_wallet(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . '/99999');

        $response->assertStatus(404);
    }

    public function test_destroy_cannot_delete_other_user_wallet(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_B, 'amount' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$wallet->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('budget_active_wallets', ['id' => $wallet->id]);
    }

    public function test_destroy_returns_422_when_wallet_has_balance(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$wallet->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('budget_active_wallets', ['id' => $wallet->id]);
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
            'name'    => 'Income Cat',
            'icon'    => 'payments',
            'color'   => '#000000',
            'type'    => 'income',
        ], $overrides));
    }

    private function createWallet(array $overrides = []): BudgetActiveWallet
    {
        return BudgetActiveWallet::create(array_merge([
            'user_id'        => self::USER_A,
            'name'           => 'Default Wallet',
            'category_name'  => 'Income Cat',
            'category_icon'  => 'payments',
            'category_color' => '#000000',
            'category_type'  => 'income',
            'amount'         => 0,
        ], $overrides));
    }
}
