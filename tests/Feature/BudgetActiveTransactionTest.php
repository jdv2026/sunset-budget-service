<?php

namespace Tests\Feature;

use App\Models\BudgetActiveCategory;
use App\Models\BudgetActiveBill;
use App\Models\BudgetActiveGoal;
use App\Models\BudgetActiveTransaction;
use App\Models\BudgetActiveWallet;
use App\Services\JwksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetActiveTransactionTest extends TestCase
{
    use RefreshDatabase;

    private const BASE          = '/api/web/budget-active-transactions';
    private const PAY_OPTIONS   = '/api/web/budget-active-transactions/pay-transfer-options';
    private const STORE_INCOME  = '/api/web/store/income/budget-active-transactions';
    private const STORE_PAY     = '/api/web/store/pay-bills/budget-active-transactions';
    private const STORE_XFER    = '/api/web/store/transfer-funds/budget-active-transactions';
    private const USER_A        = 'user-a';
    private const USER_B        = 'user-b';

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------

    public function test_requires_authentication(): void
    {
        $response = $this->withToken('')->getJson(self::BASE . '?year=' . now()->year);

        $response->assertStatus(401);
    }

    public function test_returns_401_for_invalid_token(): void
    {
        $response = $this->withToken('bad-token')->getJson(self::BASE . '?year=' . now()->year);

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // GET /web/budget-active-transactions
    // -------------------------------------------------------------------------

    public function test_index_returns_expected_structure(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '?year=' . now()->year);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'payload' => ['total_income', 'total_expense', 'balance', 'transactions'],
            ]);
    }

    public function test_index_returns_zero_totals_with_no_transactions(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '?year=' . now()->year);

        $this->assertEquals(0, $response->json('payload.total_income'));
        $this->assertEquals(0, $response->json('payload.total_expense'));
        $this->assertEquals(0, $response->json('payload.balance'));
    }

    public function test_index_returns_only_own_transactions(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 100]);
        $this->createTransaction(['user_id' => self::USER_B, 'type' => 'income', 'wallet_amount' => 200]);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '?year=' . now()->year);

        $this->assertCount(1, $response->json('payload.transactions.data'));
    }

    public function test_index_calculates_totals_correctly(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income',  'wallet_amount' => 500]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income',  'wallet_amount' => 300]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'expense', 'bill_paid'     => 200]);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '?year=' . now()->year);

        $this->assertEquals(800, $response->json('payload.total_income'));
        $this->assertEquals(200, $response->json('payload.total_expense'));
        $this->assertEquals(600, $response->json('payload.balance'));
    }

    public function test_index_filters_by_month(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 100, 'date' => now()->startOfMonth()->toDateString()]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 200, 'date' => now()->subMonth()->toDateString()]);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(
            self::BASE . '?year=' . now()->year . '&month=' . now()->month
        );

        $this->assertCount(1, $response->json('payload.transactions.data'));
        $this->assertEquals(100, $response->json('payload.total_income'));
    }

    public function test_index_returns_transactions_ordered_newest_first(): void
    {
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 1, 'date' => now()->subDays(2)->toDateString(), 'created_at' => now()->subDays(2)]);
        $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => 2, 'date' => now()->toDateString(),             'created_at' => now()]);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '?year=' . now()->year);

        $amounts = array_column($response->json('payload.transactions.data'), 'wallet_amount');
        $this->assertEquals([2, 1], $amounts);
    }

    public function test_index_respects_per_page_param(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createTransaction(['user_id' => self::USER_A, 'type' => 'income', 'wallet_amount' => $i]);
        }
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::BASE . '?year=' . now()->year . '&per_page=3');

        $this->assertCount(3, $response->json('payload.transactions.data'));
    }

    // -------------------------------------------------------------------------
    // GET /web/budget-active-transactions/pay-transfer-options
    // -------------------------------------------------------------------------

    public function test_pay_transfer_options_returns_expected_keys(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::PAY_OPTIONS);

        $response->assertStatus(200)
            ->assertJsonStructure(['payload' => ['wallets', 'goals', 'bills']]);
    }

    public function test_pay_transfer_options_returns_only_own_data(): void
    {
        $this->createWallet(['user_id' => self::USER_A, 'name' => 'My Wallet']);
        $this->createWallet(['user_id' => self::USER_B, 'name' => 'Other Wallet']);

        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->getJson(self::PAY_OPTIONS);

        $this->assertCount(1, $response->json('payload.wallets'));
        $this->assertEquals('My Wallet', $response->json('payload.wallets.0.name'));
    }

    // -------------------------------------------------------------------------
    // POST /web/store/income/budget-active-transactions
    // -------------------------------------------------------------------------

    public function test_store_income_creates_transaction(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_INCOME, [
            'wallet_id' => $wallet->id,
            'amount'    => 500,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budget_active_transactions', [
            'user_id'       => self::USER_A,
            'type'          => 'income',
            'wallet_name'   => $wallet->name,
            'wallet_amount' => 500,
        ]);
    }

    public function test_store_income_increments_wallet_amount(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 100]);
        $this->mockJwks(self::USER_A);

        $this->withToken('valid-token')->postJson(self::STORE_INCOME, [
            'wallet_id' => $wallet->id,
            'amount'    => 250,
        ]);

        $this->assertDatabaseHas('budget_active_wallets', [
            'id'     => $wallet->id,
            'amount' => 350,
        ]);
    }

    public function test_store_income_snapshots_wallet_data(): void
    {
        $wallet = $this->createWallet([
            'user_id'        => self::USER_A,
            'name'           => 'Main',
            'category_name'  => 'Salary',
            'category_icon'  => 'payments',
            'category_color' => '#00ff00',
            'category_type'  => 'income',
        ]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_INCOME, [
            'wallet_id' => $wallet->id,
            'amount'    => 100,
        ]);

        $payload = $response->json('payload');
        $this->assertEquals('Main',     $payload['wallet_name']);
        $this->assertEquals('Salary',   $payload['wallet_category_name']);
        $this->assertEquals('payments', $payload['wallet_category_icon']);
        $this->assertEquals('#00ff00',  $payload['wallet_category_color']);
    }

    public function test_store_income_validates_required_fields(): void
    {
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_INCOME, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['wallet_id', 'amount']);
    }

    public function test_store_income_validates_amount_above_zero(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_INCOME, [
            'wallet_id' => $wallet->id,
            'amount'    => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_store_income_rejects_wallet_of_other_user(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_B]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_INCOME, [
            'wallet_id' => $wallet->id,
            'amount'    => 100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['wallet_id']);
    }

    // -------------------------------------------------------------------------
    // POST /web/store/pay-bills/budget-active-transactions
    // -------------------------------------------------------------------------

    public function test_pay_bills_creates_transaction(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $bill   = $this->createBill(['user_id' => self::USER_A, 'amount' => 200, 'paid' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_PAY, [
            'amount' => 100,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "bill:{$bill->id}",
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('budget_active_transactions', [
            'user_id'    => self::USER_A,
            'type'       => 'expense',
            'bill_name'  => $bill->name,
            'bill_paid'  => 100,
        ]);
    }

    public function test_pay_bills_decrements_wallet_amount(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $bill   = $this->createBill(['user_id' => self::USER_A, 'amount' => 200, 'paid' => 0]);
        $this->mockJwks(self::USER_A);

        $this->withToken('valid-token')->postJson(self::STORE_PAY, [
            'amount' => 100,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "bill:{$bill->id}",
        ]);

        $this->assertDatabaseHas('budget_active_wallets', ['id' => $wallet->id, 'amount' => 400]);
    }

    public function test_pay_bills_increments_bill_paid(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $bill   = $this->createBill(['user_id' => self::USER_A, 'amount' => 200, 'paid' => 50]);
        $this->mockJwks(self::USER_A);

        $this->withToken('valid-token')->postJson(self::STORE_PAY, [
            'amount' => 100,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "bill:{$bill->id}",
        ]);

        $this->assertDatabaseHas('budget_active_bills', ['id' => $bill->id, 'paid' => 150]);
    }

    public function test_pay_bills_returns_422_for_insufficient_wallet_balance(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 50]);
        $bill   = $this->createBill(['user_id' => self::USER_A, 'amount' => 500, 'paid' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_PAY, [
            'amount' => 100,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "bill:{$bill->id}",
        ]);

        $response->assertStatus(422);
    }

    public function test_pay_bills_returns_422_when_bill_is_already_fully_paid(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $bill   = $this->createBill(['user_id' => self::USER_A, 'amount' => 100, 'paid' => 100]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_PAY, [
            'amount' => 50,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "bill:{$bill->id}",
        ]);

        $response->assertStatus(422);
    }

    public function test_pay_bills_returns_404_for_other_user_wallet(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_B, 'amount' => 500]);
        $bill   = $this->createBill(['user_id' => self::USER_A, 'amount' => 200, 'paid' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_PAY, [
            'amount' => 100,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "bill:{$bill->id}",
        ]);

        $response->assertStatus(404);
    }

    public function test_pay_bills_returns_404_for_other_user_bill(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $bill   = $this->createBill(['user_id' => self::USER_B, 'amount' => 200, 'paid' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_PAY, [
            'amount' => 100,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "bill:{$bill->id}",
        ]);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // POST /web/store/transfer-funds/budget-active-transactions
    // -------------------------------------------------------------------------

    public function test_transfer_wallet_to_wallet_creates_two_transactions(): void
    {
        $from = $this->createWallet(['user_id' => self::USER_A, 'name' => 'From', 'amount' => 500]);
        $to   = $this->createWallet(['user_id' => self::USER_A, 'name' => 'To',   'amount' => 0]);
        $this->mockJwks(self::USER_A);

        $this->withToken('valid-token')->postJson(self::STORE_XFER, [
            'amount' => 200,
            'from'   => "wallet:{$from->id}",
            'to'     => "wallet:{$to->id}",
        ]);

        $this->assertDatabaseCount('budget_active_transactions', 2);
    }

    public function test_transfer_wallet_to_wallet_updates_balances(): void
    {
        $from = $this->createWallet(['user_id' => self::USER_A, 'name' => 'From', 'amount' => 500]);
        $to   = $this->createWallet(['user_id' => self::USER_A, 'name' => 'To',   'amount' => 100]);
        $this->mockJwks(self::USER_A);

        $this->withToken('valid-token')->postJson(self::STORE_XFER, [
            'amount' => 200,
            'from'   => "wallet:{$from->id}",
            'to'     => "wallet:{$to->id}",
        ]);

        $this->assertDatabaseHas('budget_active_wallets', ['id' => $from->id, 'amount' => 300]);
        $this->assertDatabaseHas('budget_active_wallets', ['id' => $to->id,   'amount' => 300]);
    }

    public function test_transfer_wallet_to_goal_creates_one_transaction(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $goal   = $this->createGoal(['user_id' => self::USER_A, 'saved' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_XFER, [
            'amount' => 150,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "goal:{$goal->id}",
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('budget_active_transactions', 1);
    }

    public function test_transfer_wallet_to_goal_updates_balances(): void
    {
        $wallet = $this->createWallet(['user_id' => self::USER_A, 'amount' => 500]);
        $goal   = $this->createGoal(['user_id' => self::USER_A, 'saved' => 50]);
        $this->mockJwks(self::USER_A);

        $this->withToken('valid-token')->postJson(self::STORE_XFER, [
            'amount' => 150,
            'from'   => "wallet:{$wallet->id}",
            'to'     => "goal:{$goal->id}",
        ]);

        $this->assertDatabaseHas('budget_active_wallets', ['id' => $wallet->id, 'amount' => 350]);
        $this->assertDatabaseHas('budget_active_goals',   ['id' => $goal->id,   'saved'  => 200]);
    }

    public function test_transfer_returns_422_for_insufficient_wallet_balance(): void
    {
        $from = $this->createWallet(['user_id' => self::USER_A, 'name' => 'From', 'amount' => 50]);
        $to   = $this->createWallet(['user_id' => self::USER_A, 'name' => 'To',   'amount' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_XFER, [
            'amount' => 100,
            'from'   => "wallet:{$from->id}",
            'to'     => "wallet:{$to->id}",
        ]);

        $response->assertStatus(422);
    }

    public function test_transfer_returns_404_for_other_user_entity(): void
    {
        $from = $this->createWallet(['user_id' => self::USER_B, 'name' => 'From', 'amount' => 500]);
        $to   = $this->createWallet(['user_id' => self::USER_A, 'name' => 'To',   'amount' => 0]);
        $this->mockJwks(self::USER_A);

        $response = $this->withToken('valid-token')->postJson(self::STORE_XFER, [
            'amount' => 100,
            'from'   => "wallet:{$from->id}",
            'to'     => "wallet:{$to->id}",
        ]);

        $response->assertStatus(404);
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

    private function createCategory(string $userId, string $type = 'income'): BudgetActiveCategory
    {
        static $counter = 0;
        $counter++;

        return BudgetActiveCategory::create([
            'user_id' => $userId,
            'name'    => "Cat {$counter}",
            'icon'    => 'payments',
            'color'   => '#000000',
            'type'    => $type,
        ]);
    }

    private function createWallet(array $overrides = []): BudgetActiveWallet
    {
        static $counter = 0;
        $counter++;

        return BudgetActiveWallet::create(array_merge([
            'user_id'        => self::USER_A,
            'name'           => "Wallet {$counter}",
            'category_name'  => 'Income Cat',
            'category_icon'  => 'payments',
            'category_color' => '#000000',
            'category_type'  => 'income',
            'amount'         => 0,
        ], $overrides));
    }

    private function createBill(array $overrides = []): BudgetActiveBill
    {
        static $counter = 0;
        $counter++;

        return BudgetActiveBill::create(array_merge([
            'user_id'        => self::USER_A,
            'name'           => "Bill {$counter}",
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

    private function createGoal(array $overrides = []): BudgetActiveGoal
    {
        static $counter = 0;
        $counter++;

        return BudgetActiveGoal::create(array_merge([
            'user_id'        => self::USER_A,
            'name'           => "Goal {$counter}",
            'category_name'  => 'Goals Cat',
            'category_icon'  => 'savings',
            'category_color' => '#000000',
            'category_type'  => 'goals',
            'amount'         => 1000,
            'saved'          => 0,
        ], $overrides));
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
