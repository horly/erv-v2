<?php

namespace Tests\Feature;

use App\Models\AccountingClient;
use App\Models\AccountingCurrency;
use App\Models\AccountingPaymentMethod;
use App\Models\AccountingSalesInvoice;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceVatTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private Company $company;

    private CompanySite $site;

    private AccountingClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::query()->create([
            'name' => 'Vat Tester',
            'email' => 'vat-tester@example.test',
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
            'name' => 'Vat Company',
            'email' => 'vat-company@example.test',
            'country' => 'CD',
        ]);

        $this->site = CompanySite::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Vat Site',
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

        $this->client = AccountingClient::query()->create([
            'company_site_id' => $this->site->id,
            'created_by' => $this->superadmin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Orange RDC',
        ]);
    }

    private function createInvoice(array $attributes = []): AccountingSalesInvoice
    {
        return AccountingSalesInvoice::query()->create(array_merge([
            'company_site_id' => $this->site->id,
            'client_id' => $this->client->id,
            'created_by' => $this->superadmin->id,
            'invoice_date' => '2026-11-01',
            'due_date' => '2026-11-30',
            'currency' => 'USD',
            'status' => AccountingSalesInvoice::STATUS_PAID,
            'subtotal' => 1000,
            'discount_total' => 0,
            'total_ht' => 1000,
            'tax_rate' => 16,
            'tax_amount' => 160,
            'total_ttc' => 1160,
            'paid_total' => 1160,
            'balance_due' => 0,
        ], $attributes));
    }

    public function test_vat_can_be_marked_paid_on_settled_invoice(): void
    {
        $invoice = $this->createInvoice();

        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.sales-invoices.tax-payment.store', [$this->company, $this->site, $invoice]), [
                'tax_payment_reference' => 'QUIT-2026-001',
            ])
            ->assertRedirect(route('main.accounting.sales-invoices', [$this->company, $this->site]));

        $invoice->refresh();
        $this->assertNotNull($invoice->tax_paid_at);
        $this->assertSame($this->superadmin->id, (int) $invoice->tax_paid_by);
        $this->assertSame('QUIT-2026-001', $invoice->tax_payment_reference);
    }

    public function test_vat_cannot_be_marked_paid_on_unsettled_invoice(): void
    {
        $invoice = $this->createInvoice([
            'status' => AccountingSalesInvoice::STATUS_PARTIALLY_PAID,
            'paid_total' => 500,
            'balance_due' => 660,
        ]);

        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.sales-invoices.tax-payment.store', [$this->company, $this->site, $invoice]))
            ->assertRedirect(route('main.accounting.sales-invoices', [$this->company, $this->site]))
            ->assertSessionHas('toast_type', 'danger');

        $this->assertNull($invoice->refresh()->tax_paid_at);
    }

    public function test_vat_payment_can_be_cancelled(): void
    {
        $invoice = $this->createInvoice([
            'tax_paid_at' => now(),
            'tax_paid_by' => $this->superadmin->id,
            'tax_payment_reference' => 'QUIT-2026-002',
        ]);

        $this->actingAs($this->superadmin)
            ->delete(route('main.accounting.sales-invoices.tax-payment.destroy', [$this->company, $this->site, $invoice]))
            ->assertRedirect(route('main.accounting.sales-invoices', [$this->company, $this->site]));

        $invoice->refresh();
        $this->assertNull($invoice->tax_paid_at);
        $this->assertNull($invoice->tax_paid_by);
        $this->assertNull($invoice->tax_payment_reference);
    }

    public function test_invoice_without_vat_cannot_be_marked(): void
    {
        $invoice = $this->createInvoice([
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_ttc' => 1000,
            'paid_total' => 1000,
        ]);

        $this->actingAs($this->superadmin)
            ->post(route('main.accounting.sales-invoices.tax-payment.store', [$this->company, $this->site, $invoice]))
            ->assertRedirect(route('main.accounting.sales-invoices', [$this->company, $this->site]));

        $this->assertNull($invoice->refresh()->tax_paid_at);
    }

    public function test_sales_invoices_page_filters_by_vat_status(): void
    {
        $pendingInvoice = $this->createInvoice();
        $paidInvoice = $this->createInvoice([
            'tax_paid_at' => now(),
            'tax_paid_by' => $this->superadmin->id,
        ]);

        // Les références sont vérifiées via les cellules du tableau (reference-pill),
        // car la cloche de notifications du topbar mentionne toutes les factures.
        $rowReference = fn (AccountingSalesInvoice $invoice): string => 'reference-pill">'.$invoice->reference.'<';

        $this->actingAs($this->superadmin)
            ->get(route('main.accounting.sales-invoices', [$this->company, $this->site, 'tax_status' => 'pending']))
            ->assertOk()
            ->assertSee($rowReference($pendingInvoice), false)
            ->assertDontSee($rowReference($paidInvoice), false);

        $this->actingAs($this->superadmin)
            ->get(route('main.accounting.sales-invoices', [$this->company, $this->site, 'tax_status' => 'paid']))
            ->assertOk()
            ->assertSee($rowReference($paidInvoice), false)
            ->assertDontSee($rowReference($pendingInvoice), false);
    }

    public function test_vat_report_section_shows_totals(): void
    {
        $this->createInvoice();
        $this->createInvoice([
            'tax_amount' => 40,
            'total_ttc' => 1040,
            'paid_total' => 1040,
            'tax_paid_at' => now(),
            'tax_paid_by' => $this->superadmin->id,
        ]);

        $this->actingAs($this->superadmin)
            ->get(route('main.accounting.reports', [$this->company, $this->site, 'section' => 'vat']))
            ->assertOk()
            ->assertSee(__('main.report_vat_collected'))
            ->assertSee(__('main.report_vat_due'))
            ->assertSee('200,00 USD')
            ->assertSee('40,00 USD')
            ->assertSee('160,00 USD');
    }
}
