<?php

namespace Tests\Feature;

use App\Models\AccountingCurrency;
use App\Models\AccountingExpense;
use App\Models\AccountingExpenseCategory;
use App\Models\AccountingOtherIncome;
use App\Models\AccountingPaymentMethod;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashPaymentMethodFundTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private Company $company;

    private CompanySite $site;

    private AccountingPaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::query()->create([
            'name' => 'Cash Tester',
            'email' => 'cash-tester@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $subscription = Subscription::query()->create([
            'name' => 'Test plan',
            'code' => 'TEST-PLAN',
            'type' => 'pro',
            'status' => 'active',
            'company_limit' => 5,
        ]);

        $this->company = Company::query()->create([
            'subscription_id' => $subscription->id,
            'created_by' => $this->superadmin->id,
            'name' => 'Cash Company',
            'email' => 'cash-company@example.test',
            'country' => 'CD',
        ]);

        $this->site = CompanySite::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Cash Site',
            'type' => 'billing',
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'USD',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);

        AccountingCurrency::query()->create([
            'company_site_id' => $this->site->id,
            'created_by' => $this->superadmin->id,
            'code' => 'USD',
            'name' => 'Dollar américain',
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_base' => true,
            'is_default' => true,
            'status' => AccountingCurrency::STATUS_ACTIVE,
        ]);

        $this->cashMethod = AccountingPaymentMethod::query()->create([
            'company_site_id' => $this->site->id,
            'created_by' => $this->superadmin->id,
            'name' => 'Espèces',
            'type' => AccountingPaymentMethod::TYPE_CASH,
            'currency_code' => 'USD',
            'is_default' => true,
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
        ]);
    }

    private function createExpenseCategory(): AccountingExpenseCategory
    {
        return AccountingExpenseCategory::query()->create([
            'company_site_id' => $this->site->id,
            'created_by' => $this->superadmin->id,
            'name' => 'Fournitures',
            'slug' => 'fournitures',
            'status' => AccountingExpenseCategory::STATUS_ACTIVE,
        ]);
    }

    private function fundCash(float $amount): void
    {
        AccountingOtherIncome::query()->create([
            'company_site_id' => $this->site->id,
            'payment_method_id' => $this->cashMethod->id,
            'created_by' => $this->superadmin->id,
            'income_date' => '2026-11-01',
            'type' => AccountingOtherIncome::TYPE_POSITIVE_ADJUSTMENT,
            'label' => 'Fond de caisse initial',
            'amount' => $amount,
            'currency' => 'USD',
            'status' => AccountingOtherIncome::STATUS_VALIDATED,
        ]);
    }

    public function test_cash_funding_creates_validated_other_income_and_is_displayed(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.payment-methods.cash-movements.store', [$this->company, $this->site, $this->cashMethod]), [
                'cash_movement_method_id' => $this->cashMethod->id,
                'cash_type' => 'funding',
                'cash_movement_date' => '2026-11-02',
                'cash_amount' => '150.00',
                'cash_label' => 'Fond de caisse initial',
            ])
            ->assertRedirect(route('main.accounting.payment-methods', [$this->company, $this->site]));

        $this->assertDatabaseHas('accounting_other_incomes', [
            'payment_method_id' => $this->cashMethod->id,
            'type' => AccountingOtherIncome::TYPE_POSITIVE_ADJUSTMENT,
            'label' => 'Fond de caisse initial',
            'amount' => '150.00',
            'currency' => 'USD',
            'status' => AccountingOtherIncome::STATUS_VALIDATED,
        ]);

        $this->actingAs($this->superadmin)
            ->get(route('main.accounting.payment-methods', [$this->company, $this->site]))
            ->assertOk()
            ->assertSee('150,00');
    }

    public function test_cash_withdrawal_creates_validated_expense_and_cannot_exceed_balance(): void
    {
        $this->fundCash(100);

        $this->actingAs($this->superadmin)
            ->from(route('main.accounting.payment-methods', [$this->company, $this->site]))
            ->post(route('main.accounting.payment-methods.cash-movements.store', [$this->company, $this->site, $this->cashMethod]), [
                'cash_movement_method_id' => $this->cashMethod->id,
                'cash_type' => 'withdrawal',
                'cash_movement_date' => '2026-11-02',
                'cash_amount' => '250.00',
                'cash_label' => 'Versement en banque',
            ])
            ->assertSessionHasErrors('cash_amount');

        $this->assertDatabaseMissing('accounting_expenses', [
            'label' => 'Versement en banque',
        ]);

        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.payment-methods.cash-movements.store', [$this->company, $this->site, $this->cashMethod]), [
                'cash_movement_method_id' => $this->cashMethod->id,
                'cash_type' => 'withdrawal',
                'cash_movement_date' => '2026-11-02',
                'cash_amount' => '60.00',
                'cash_label' => 'Versement en banque',
            ])
            ->assertRedirect(route('main.accounting.payment-methods', [$this->company, $this->site]));

        $this->assertDatabaseHas('accounting_expenses', [
            'payment_method_id' => $this->cashMethod->id,
            'label' => 'Versement en banque',
            'amount' => '60.00',
            'status' => AccountingExpense::STATUS_VALIDATED,
        ]);

        $category = AccountingExpenseCategory::query()
            ->where('company_site_id', $this->site->id)
            ->where('slug', 'operations-caisse')
            ->first();

        $this->assertNotNull($category);
    }

    public function test_records_created_before_cash_tracking_are_ignored(): void
    {
        $this->fundCash(500);

        // Simule un encaissement enregistré avant l'activation du suivi de caisse.
        DB::table('accounting_other_incomes')->update([
            'created_at' => now()->subYears(2),
        ]);

        $this->cashMethod->update(['cash_tracking_started_at' => now()->subDay()]);

        $category = $this->createExpenseCategory();

        $this->actingAs($this->superadmin)
            ->from(route('main.accounting.expenses', [$this->company, $this->site]))
            ->post(route('main.accounting.expenses.store', [$this->company, $this->site]), [
                'expense_date' => '2026-11-02',
                'expense_category_id' => $category->id,
                'label' => 'Dépense post-activation',
                'amount' => '50.00',
                'currency' => 'USD',
                'payment_method_id' => $this->cashMethod->id,
                'status' => AccountingExpense::STATUS_VALIDATED,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('accounting_expenses', [
            'label' => 'Dépense post-activation',
        ]);
    }

    public function test_validated_cash_expense_is_blocked_when_fund_is_insufficient(): void
    {
        $category = $this->createExpenseCategory();
        $this->fundCash(50);

        $payload = [
            'expense_date' => '2026-11-02',
            'expense_category_id' => $category->id,
            'label' => 'Achat fournitures',
            'amount' => '80.00',
            'currency' => 'USD',
            'payment_method_id' => $this->cashMethod->id,
            'status' => AccountingExpense::STATUS_VALIDATED,
        ];

        $this->actingAs($this->superadmin)
            ->from(route('main.accounting.expenses', [$this->company, $this->site]))
            ->post(route('main.accounting.expenses.store', [$this->company, $this->site]), $payload)
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('accounting_expenses', [
            'company_site_id' => $this->site->id,
            'label' => 'Achat fournitures',
        ]);

        $this->actingAs($this->superadmin)
            ->from(route('main.accounting.expenses', [$this->company, $this->site]))
            ->post(route('main.accounting.expenses.store', [$this->company, $this->site]), array_merge($payload, ['amount' => '30.00']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('accounting_expenses', [
            'company_site_id' => $this->site->id,
            'label' => 'Achat fournitures',
            'amount' => '30.00',
            'status' => AccountingExpense::STATUS_VALIDATED,
        ]);
    }

    public function test_expense_validation_is_blocked_when_cash_fund_is_insufficient(): void
    {
        $category = $this->createExpenseCategory();

        $expense = AccountingExpense::query()->create([
            'company_site_id' => $this->site->id,
            'created_by' => $this->superadmin->id,
            'expense_category_id' => $category->id,
            'expense_date' => '2026-11-02',
            'label' => 'Grosse dépense',
            'amount' => 500,
            'currency' => 'USD',
            'payment_method_id' => $this->cashMethod->id,
            'status' => AccountingExpense::STATUS_DRAFT,
        ]);

        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.expenses.validate', [$this->company, $this->site, $expense]))
            ->assertRedirect(route('main.accounting.expenses', [$this->company, $this->site]))
            ->assertSessionHas('toast_type', 'danger');

        $this->assertDatabaseHas('accounting_expenses', [
            'id' => $expense->id,
            'status' => AccountingExpense::STATUS_DRAFT,
        ]);
    }

    public function test_cancelling_cash_income_is_blocked_when_balance_would_become_negative(): void
    {
        $this->fundCash(100);

        $funding = AccountingOtherIncome::query()
            ->where('company_site_id', $this->site->id)
            ->firstOrFail();

        // Un retrait consomme une partie du fond : l'annulation de l'entrée rendrait la caisse négative.
        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.payment-methods.cash-movements.store', [$this->company, $this->site, $this->cashMethod]), [
                'cash_movement_method_id' => $this->cashMethod->id,
                'cash_type' => 'withdrawal',
                'cash_movement_date' => '2026-11-02',
                'cash_amount' => '60.00',
                'cash_label' => 'Retrait partiel',
            ]);

        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.other-incomes.cancel', [$this->company, $this->site, $funding]))
            ->assertRedirect(route('main.accounting.other-incomes', [$this->company, $this->site]))
            ->assertSessionHas('toast_type', 'danger');

        $this->assertDatabaseHas('accounting_other_incomes', [
            'id' => $funding->id,
            'status' => AccountingOtherIncome::STATUS_VALIDATED,
        ]);
    }

    public function test_bank_method_is_not_restricted_by_cash_fund(): void
    {
        $bankMethod = AccountingPaymentMethod::query()->create([
            'company_site_id' => $this->site->id,
            'created_by' => $this->superadmin->id,
            'name' => 'Banque',
            'type' => AccountingPaymentMethod::TYPE_BANK,
            'currency_code' => 'USD',
            'status' => AccountingPaymentMethod::STATUS_ACTIVE,
        ]);

        $category = $this->createExpenseCategory();

        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.expenses.store', [$this->company, $this->site]), [
                'expense_date' => '2026-11-02',
                'expense_category_id' => $category->id,
                'label' => 'Dépense bancaire',
                'amount' => '900.00',
                'currency' => 'USD',
                'payment_method_id' => $bankMethod->id,
                'status' => AccountingExpense::STATUS_VALIDATED,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('accounting_expenses', [
            'label' => 'Dépense bancaire',
            'status' => AccountingExpense::STATUS_VALIDATED,
        ]);
    }
}
