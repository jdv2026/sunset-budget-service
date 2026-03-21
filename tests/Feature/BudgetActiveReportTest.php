<?php

namespace Tests\Feature;

use App\Models\BudgetActiveTransaction;
use App\Services\JwksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetActiveReportTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/web/budget-active-report';
    private const USER_A   = 'user-a';
    private const USER_B   = 'user-b';

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------

    public function test_requires_authentication(): void
    {
        $response = $this->withToken('')->getJson(self::ENDPOINT);

        $response->assertStatus(401);
    }

    public function test_returns_401_for_invalid_token(): void
    {
        $response = $this->withToken('bad-token')->getJson(self::ENDPOINT);

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Response structure
    // -------------------------------------------------------------------------

    public function test_returns_expected_structure(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'payload' => [
                    'total_income',
                    'total_expense',
                    'balance',
                    'savings_rate',
                    'transactions_income_expense',
                ],
            ]);
    }

    public function test_returns_zero_defaults_with_no_data(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $payload = $response->json('payload');
        $this->assertEquals(0, $payload['total_income']);
        $this->assertEquals(0, $payload['total_expense']);
        $this->assertEquals(0, $payload['balance']);
        $this->assertEquals(0, $payload['savings_rate']);
        $this->assertEmpty($payload['transactions_income_expense']);
    }

    // -------------------------------------------------------------------------
    // Calculations
    // -------------------------------------------------------------------------

    public function test_total_income_sums_income_wallet_amounts(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 300]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 200]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(500, $response->json('payload.total_income'));
    }

    public function test_total_expense_sums_expense_bill_paid(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'expense', 'bill_paid' => 120]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'expense', 'bill_paid' => 80]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(200, $response->json('payload.total_expense'));
    }

    public function test_balance_is_income_minus_expense(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income',  'wallet_amount' => 1000]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'expense', 'bill_paid'     => 350]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(650, $response->json('payload.balance'));
    }

    public function test_savings_rate_is_calculated_correctly(): void
    {
        // income=800, expense=200, balance=600, rate=75%
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income',  'wallet_amount' => 800]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'expense', 'bill_paid'     => 200]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(75, $response->json('payload.savings_rate'));
    }

    public function test_savings_rate_is_zero_when_no_income(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'expense', 'bill_paid' => 100]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(0, $response->json('payload.savings_rate'));
    }

    public function test_transfer_type_is_excluded(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income',   'wallet_amount' => 500]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'transfer', 'wallet_amount' => 999]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(500, $response->json('payload.total_income'));
        $this->assertCount(1, $response->json('payload.transactions_income_expense'));
    }

    public function test_results_are_isolated_to_own_user(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 300]);
        $this->createTransaction(['user_id' => self::USER_B, 'type' => 'income', 'wallet_amount' => 999]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(300, $response->json('payload.total_income'));
        $this->assertCount(1, $response->json('payload.transactions_income_expense'));
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    public function test_defaults_to_current_year(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 100, 'date' => now()->toDateString()]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 999, 'date' => now()->subYear()->toDateString()]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $this->assertEquals(100, $response->json('payload.total_income'));
    }

    public function test_filters_by_year(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 100, 'date' => now()->toDateString()]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 999, 'date' => now()->subYear()->toDateString()]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT . '?year=' . now()->year);

        $this->assertEquals(100, $response->json('payload.total_income'));
    }

    public function test_filters_by_month_and_year(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 100, 'date' => now()->startOfMonth()->toDateString()]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 999, 'date' => now()->subMonth()->toDateString()]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(
            self::ENDPOINT . '?year=' . now()->year . '&month=' . now()->month
        );

        $this->assertEquals(100, $response->json('payload.total_income'));
    }

    // -------------------------------------------------------------------------
    // Transactions list
    // -------------------------------------------------------------------------

    public function test_transactions_are_mapped_with_expected_fields(): void
    {
        $this->createTransaction([
            'user_id'       => self::USER_A,
            'type'          => 'income',
            'description'   => 'cash in',
            'wallet_amount' => 100,
            'wallet_name'   => 'Main',
        ]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $tx = $response->json('payload.transactions_income_expense.0');
        foreach (['id', 'type', 'description', 'date', 'wallet', 'wallet_name', 'wallet_color'] as $key) {
            $this->assertArrayHasKey($key, $tx);
        }
    }

    public function test_transactions_ordered_newest_first(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 1, 'date' => now()->subDays(2)->toDateString(), 'created_at' => now()->subDays(2)]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 2, 'date' => now()->toDateString(),             'created_at' => now()]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::ENDPOINT);

        $amounts = array_column($response->json('payload.transactions_income_expense'), 'wallet');
        $this->assertEquals([2, 1], $amounts);
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

    private function createTransaction(array $overrides = []): BudgetActiveTransaction
    {
        return BudgetActiveTransaction::create(array_merge([
            'user_id' => self::USER_A,
            'type'    => 'income',
            'date'    => now()->toDateString(),
        ], $overrides));
    }
}
