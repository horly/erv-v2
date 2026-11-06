<?php

namespace Tests\Feature;

use App\Models\AccountingClient;
use App\Models\AccountingNotification;
use App\Models\AccountingSalesInvoice;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\Subscription;
use App\Models\User;
use App\Support\AccountingActivityFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingNotificationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_read_another_users_cash_sale_through_notifications(): void
    {
        $subscription = Subscription::create([
            'name' => 'Notification plan',
            'code' => 'NOTIFICATION-PLAN',
            'type' => 'business',
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'subscription_id' => $subscription->id,
            'role' => User::ROLE_ADMIN,
        ]);
        $cashier = User::factory()->create([
            'subscription_id' => $subscription->id,
            'role' => User::ROLE_USER,
        ]);
        $company = Company::create([
            'subscription_id' => $subscription->id,
            'created_by' => $admin->id,
            'name' => 'Notification Company',
            'email' => 'notifications@example.test',
            'country' => 'CD',
        ]);
        $site = CompanySite::create([
            'company_id' => $company->id,
            'responsible_id' => $admin->id,
            'name' => 'Notification Site',
            'type' => CompanySite::TYPE_OFFICE,
            'modules' => [CompanySite::MODULE_ACCOUNTING],
            'currency' => 'CDF',
            'status' => CompanySite::STATUS_ACTIVE,
        ]);
        $cashier->sites()->attach($site->id, [
            'module_permissions' => json_encode([CompanySite::MODULE_ACCOUNTING => [AccountingSalesInvoice::TITLE_CASH_REGISTER]]),
            'can_create' => true,
            'can_update' => true,
            'can_delete' => false,
        ]);
        $client = AccountingClient::create([
            'company_site_id' => $site->id,
            'created_by' => $admin->id,
            'type' => AccountingClient::TYPE_COMPANY,
            'name' => 'Notification Client',
        ]);
        $attributes = [
            'company_site_id' => $site->id,
            'client_id' => $client->id,
            'title' => AccountingSalesInvoice::TITLE_CASH_REGISTER,
            'invoice_date' => today(),
            'due_date' => today()->addMonth(),
            'currency' => 'CDF',
            'status' => AccountingSalesInvoice::STATUS_PAID,
        ];
        $hiddenInvoice = AccountingSalesInvoice::create($attributes + [
            'created_by' => $admin->id,
            'reference' => 'CASH-PRIVATE-ADMIN',
        ]);
        $ownInvoice = AccountingSalesInvoice::create($attributes + [
            'created_by' => $cashier->id,
            'reference' => 'CASH-OWN-USER',
        ]);

        $feed = AccountingActivityFeed::forSite($site, $cashier, 100, CompanySite::MODULE_ACCOUNTING);
        $this->assertContains($ownInvoice->reference, array_column($feed, 'reference'));
        $this->assertNotContains($hiddenInvoice->reference, array_column($feed, 'reference'));
        $cashierUnread = AccountingActivityFeed::unreadCount($site, $cashier, CompanySite::MODULE_ACCOUNTING);
        $this->assertSame($cashierUnread + 1, AccountingActivityFeed::unreadCount($site, $admin, CompanySite::MODULE_ACCOUNTING));

        $hiddenNotification = AccountingNotification::where('subject_type', AccountingSalesInvoice::class)
            ->where('subject_id', $hiddenInvoice->id)->firstOrFail();
        $ownNotification = AccountingNotification::where('subject_type', AccountingSalesInvoice::class)
            ->where('subject_id', $ownInvoice->id)->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('main.accounting.notifications', [$company, $site]))
            ->assertOk()
            ->assertSee($ownInvoice->reference)
            ->assertDontSee($hiddenInvoice->reference);

        $this->get(route('main.accounting.notifications.show', [$company, $site, $hiddenNotification]))
            ->assertNotFound();
        $this->assertDatabaseMissing('accounting_notification_reads', [
            'accounting_notification_id' => $hiddenNotification->id,
            'user_id' => $cashier->id,
        ]);
        $this->get(route('main.accounting.notifications.show', [$company, $site, $ownNotification]))
            ->assertOk()
            ->assertSee($ownInvoice->reference)
            ->assertDontSee($hiddenInvoice->reference);

        $this->actingAs($admin)
            ->get(route('main.accounting.notifications.show', [$company, $site, $hiddenNotification]))
            ->assertOk()
            ->assertSee($hiddenInvoice->reference);
    }
}
