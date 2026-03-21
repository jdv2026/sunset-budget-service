<?php

namespace Tests\Feature;

use App\Models\BudgetActiveCategory;
use App\Models\BudgetActiveBill;
use App\Services\JwksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetActiveBillTest extends TestCase
{
    use RefreshDatabase;

    private const BASE   = '/api/web/budget-active-bills';
    private const STORE  = '/api/web/store/budget-active-bills';
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
    // GET /web/budget-active-bills
    // -------------------------------------------------------------------------

    public function test_index_returns_empty_for_new_user(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('payload'));
    }

    public function test_index_returns_only_own_bills(): void
    {
        $this->createBill(['user_id' => self::USER_A, 'name' => 'Rent']);
        $this->createBill(['user_id' => self::USER_B, 'name' => 'Wifi']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload'));
        $this->assertEquals('Rent', $response->json('payload.0.name'));
    }

    public function test_index_returns_bills_sorted_by_name(): void
    {
        $this->createBill(['user_id' => self::USER_A, 'name' => 'Water']);
        $this->createBill(['user_id' => self::USER_A, 'name' => 'Electric']);
        $this->createBill(['user_id' => self::USER_A, 'name' => 'Internet']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE);

        $names = array_column($response->json('payload'), 'name');
        $this->assertEquals(['Electric', 'Internet', 'Water'], $names);
    }

    // -------------------------------------------------------------------------
    // POST /web/store/budget-active-bills
    // -------------------------------------------------------------------------

    public function test_store_creates_bill(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budget_active_bills', [
            'user_id'   => self::USER_A,
            'name'      => 'Electric',
            'amount'    => 120,
            'frequency' => 'monthly',
            'paid'      => 0,
        ]);
    }

    public function test_store_denormalizes_category_fields(): void
    {
        $category = $this->createCategory(self::USER_A, [
            'name'  => 'Utilities',
            'icon'  => 'bolt',
            'color' => '#ffcc00',
            'type'  => 'expense',
        ]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(201);
        $payload = $response->json('payload');
        $this->assertEquals('Utilities', $payload['category_name']);
        $this->assertEquals('bolt',      $payload['category_icon']);
        $this->assertEquals('#ffcc00',   $payload['category_color']);
        $this->assertEquals('expense',   $payload['category_type']);
    }

    public function test_store_sets_upcoming_status_for_future_due_date(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => now()->addMonth()->toDateString(),
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('upcoming', $response->json('payload.status'));
    }

    public function test_store_sets_upcoming_status_for_today_due_date(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => now()->toDateString(),
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('upcoming', $response->json('payload.status'));
    }

    public function test_store_sets_overdue_status_for_past_due_date(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => now()->subMonth()->toDateString(),
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('overdue', $response->json('payload.status'));
    }

    public function test_store_accepts_optional_description(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
            'description' => 'Monthly electricity bill',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('Monthly electricity bill', $response->json('payload.description'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'amount', 'due_date', 'frequency']);
    }

    public function test_store_rejects_non_expense_category(): void
    {
        $category = $this->createCategory(self::USER_A, ['type' => 'income']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_store_validates_unique_name_per_user(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->createBill(['user_id' => self::USER_A, 'name' => 'Electric']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_allows_same_name_for_different_users(): void
    {
        $this->createBill(['user_id' => self::USER_B, 'name' => 'Electric']);
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(201);
    }

    public function test_store_validates_frequency_enum(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => 120,
            'due_date'    => '2027-01-01',
            'frequency'   => 'daily',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency']);
    }

    public function test_store_validates_amount_is_non_negative(): void
    {
        $category = $this->createCategory(self::USER_A);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE, [
            'category_id' => $category->id,
            'name'        => 'Electric',
            'amount'      => -1,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    // -------------------------------------------------------------------------
    // PUT /web/budget-active-bills/{id}
    // -------------------------------------------------------------------------

    public function test_update_modifies_bill(): void
    {
        $category = $this->createCategory(self::USER_A);
        $bill     = $this->createBill(['user_id' => self::USER_A, 'name' => 'Old Name', 'amount' => 50]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$bill->id}", [
            'category_id' => $category->id,
            'name'        => 'New Name',
            'amount'      => 200,
            'due_date'    => '2027-06-01',
            'frequency'   => 'yearly',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_bills', [
            'id'        => $bill->id,
            'name'      => 'New Name',
            'amount'    => 200,
            'frequency' => 'yearly',
        ]);
    }

    public function test_update_denormalizes_category_fields(): void
    {
        $category = $this->createCategory(self::USER_A, [
            'name'  => 'Updated Cat',
            'icon'  => 'new_icon',
            'color' => '#abcdef',
            'type'  => 'expense',
        ]);
        $bill = $this->createBill(['user_id' => self::USER_A, 'name' => 'Rent']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$bill->id}", [
            'category_id' => $category->id,
            'name'        => 'Rent',
            'amount'      => 800,
            'due_date'    => '2027-06-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('budget_active_bills', [
            'id'             => $bill->id,
            'category_name'  => 'Updated Cat',
            'category_icon'  => 'new_icon',
            'category_color' => '#abcdef',
        ]);
    }

    public function test_update_returns_404_for_other_user_bill(): void
    {
        $category = $this->createCategory(self::USER_A);
        $bill     = $this->createBill(['user_id' => self::USER_B, 'name' => 'Rent']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$bill->id}", [
            'category_id' => $category->id,
            'name'        => 'Hacked',
            'amount'      => 1,
            'due_date'    => '2027-01-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(404);
    }

    public function test_update_allows_same_name_on_same_record(): void
    {
        $category = $this->createCategory(self::USER_A);
        $bill     = $this->createBill(['user_id' => self::USER_A, 'name' => 'Rent']);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$bill->id}", [
            'category_id' => $category->id,
            'name'        => 'Rent',
            'amount'      => 900,
            'due_date'    => '2027-06-01',
            'frequency'   => 'monthly',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_validates_required_fields(): void
    {
        $bill = $this->createBill(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->putJson(self::BASE . "/{$bill->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'amount', 'due_date', 'frequency']);
    }

    // -------------------------------------------------------------------------
    // DELETE /web/budget-active-bills/{id}
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_bill(): void
    {
        $bill = $this->createBill(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$bill->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('budget_active_bills', ['id' => $bill->id]);
    }

    public function test_destroy_returns_404_for_missing_bill(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . '/99999');

        $response->assertStatus(404);
    }

    public function test_destroy_cannot_delete_other_user_bill(): void
    {
        $bill = $this->createBill(['user_id' => self::USER_B]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$bill->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('budget_active_bills', ['id' => $bill->id]);
    }

    public function test_destroy_blocked_when_bill_has_paid_amount(): void
    {
        $bill = $this->createBill(['user_id' => self::USER_A, 'paid' => 50]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->deleteJson(self::BASE . "/{$bill->id}");

        $response->assertStatus(500);
        $this->assertDatabaseHas('budget_active_bills', ['id' => $bill->id]);
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
            'name'    => 'Expense Cat',
            'icon'    => 'bolt',
            'color'   => '#000000',
            'type'    => 'expense',
        ], $overrides));
    }

    private function createBill(array $overrides = []): BudgetActiveBill
    {
        return BudgetActiveBill::create(array_merge([
            'user_id'        => self::USER_A,
            'name'           => 'Default Bill',
            'category_name'  => 'Expense Cat',
            'category_icon'  => 'bolt',
            'category_color' => '#000000',
            'category_type'  => 'expense',
            'amount'         => 100,
            'paid'           => 0,
            'due_date'       => '2027-01-01',
            'frequency'      => 'monthly',
            'status'         => 'upcoming',
        ], $overrides));
    }
}
