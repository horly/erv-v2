<?php

namespace App\Support;

use App\Models\AccountingBankReconciliation;
use App\Models\AccountingCreditNote;
use App\Models\AccountingExpense;
use App\Models\AccountingNotification;
use App\Models\AccountingOtherIncome;
use App\Models\AccountingPaymentReminder;
use App\Models\AccountingPurchase;
use App\Models\AccountingPurchaseOrder;
use App\Models\AccountingSalesInvoice;
use App\Models\AccountingTask;
use App\Models\AccountingTreasuryMovement;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AccountingActivityFeed
{
    public static function forSite(CompanySite $site, ?User $user = null, int $limit = 10): array
    {
        self::syncSite($site);

        return AccountingNotification::query()
            ->with(['actor:id,name,email', 'reads' => fn ($query) => $user ? $query->where('user_id', $user->id) : $query->whereRaw('1 = 0')])
            ->where('company_site_id', $site->id)
            ->latest('occurred_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AccountingNotification $notification): array => self::present($notification, $user))
            ->all();
    }

    public static function unreadCount(CompanySite $site, User $user): int
    {
        self::syncSite($site);

        return AccountingNotification::query()
            ->where('company_site_id', $site->id)
            ->whereDoesntHave('reads', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNotNull('read_at'))
            ->count();
    }

    public static function syncSite(CompanySite $site): void
    {
        collect()
            ->concat(self::records(AccountingSalesInvoice::class, $site, 'added_invoice', __('main.activity_added_invoice'), 'sales-invoices', 'bi-receipt', 'created_at'))
            ->concat(self::records(AccountingPurchase::class, $site, 'added_purchase', __('main.activity_added_purchase'), 'purchases', 'bi-bag-check', 'created_at'))
            ->concat(self::records(AccountingPurchaseOrder::class, $site, 'added_purchase_order', __('main.activity_added_purchase_order'), 'purchase-orders', 'bi-clipboard-check', 'created_at'))
            ->concat(self::records(AccountingCreditNote::class, $site, 'added_credit_note', __('main.activity_added_credit_note'), 'credit-notes', 'bi-arrow-counterclockwise', 'created_at'))
            ->concat(self::records(AccountingExpense::class, $site, 'added_expense', __('main.activity_added_expense'), 'expenses', 'bi-wallet2', 'created_at'))
            ->concat(self::records(AccountingOtherIncome::class, $site, 'added_other_income', __('main.activity_added_other_income'), 'other-incomes', 'bi-plus-circle', 'created_at'))
            ->concat(self::records(AccountingPaymentReminder::class, $site, 'sent_payment_reminder', __('main.activity_sent_payment_reminder'), 'payment-reminders', 'bi-bell', 'created_at'))
            ->concat(self::records(AccountingTask::class, $site, 'added_task', __('main.activity_added_task'), 'tasks', 'bi-check2-square', 'created_at'))
            ->concat(self::records(AccountingBankReconciliation::class, $site, 'added_bank_reconciliation', __('main.activity_added_bank_reconciliation'), 'bank-reconciliations', 'bi-bank', 'created_at'))
            ->concat(self::records(AccountingTreasuryMovement::class, $site, 'added_treasury_movement', __('main.activity_added_treasury_movement'), 'treasury', 'bi-activity', 'created_at'))
            ->concat(self::statusRecords(AccountingExpense::class, $site, AccountingExpense::STATUS_VALIDATED, 'validated_expense', __('main.activity_validated_expense'), 'expenses', 'bi-check2-circle'))
            ->concat(self::statusRecords(AccountingOtherIncome::class, $site, AccountingOtherIncome::STATUS_VALIDATED, 'validated_other_income', __('main.activity_validated_other_income'), 'other-incomes', 'bi-check2-circle'))
            ->concat(self::statusRecords(AccountingCreditNote::class, $site, AccountingCreditNote::STATUS_VALIDATED, 'validated_credit_note', __('main.activity_validated_credit_note'), 'credit-notes', 'bi-check2-circle'))
            ->concat(self::statusRecords(AccountingTask::class, $site, AccountingTask::STATUS_COMPLETED, 'completed_task', __('main.activity_completed_task'), 'tasks', 'bi-check2-square'))
            ->each(fn (array $payload): AccountingNotification => AccountingNotification::query()->updateOrCreate(
                [
                    'action_key' => $payload['action_key'],
                    'subject_type' => $payload['subject_type'],
                    'subject_id' => $payload['subject_id'],
                ],
                $payload,
            ));
    }

    public static function present(AccountingNotification $notification, ?User $user = null): array
    {
        return [
            'id' => $notification->id,
            'actor' => $notification->actor?->name ?: __('main.system_user'),
            'action' => $notification->title,
            'reference' => $notification->subject_reference,
            'icon' => $notification->icon,
            'date' => $notification->occurred_at,
            'time' => $notification->occurred_at?->diffForHumans() ?: '',
            'is_read' => $user ? $notification->isReadBy($user) : false,
        ];
    }

    private static function records(string $modelClass, CompanySite $site, string $actionKey, string $title, string $moduleKey, string $icon, string $dateColumn): Collection
    {
        return $modelClass::query()
            ->with('creator:id,name,email')
            ->where('company_site_id', $site->id)
            ->latest($dateColumn)
            ->limit(8)
            ->get()
            ->map(fn (Model $record): array => self::payload($site, $record, $actionKey, $title, $moduleKey, $icon, $record->{$dateColumn}));
    }

    private static function statusRecords(string $modelClass, CompanySite $site, string $status, string $actionKey, string $title, string $moduleKey, string $icon): Collection
    {
        return $modelClass::query()
            ->with('creator:id,name,email')
            ->where('company_site_id', $site->id)
            ->where('status', $status)
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (Model $record): array => self::payload($site, $record, $actionKey, $title, $moduleKey, $icon, $record->updated_at));
    }

    private static function payload(CompanySite $site, Model $record, string $actionKey, string $title, string $moduleKey, string $icon, mixed $date): array
    {
        $date = $date ?: now();

        return [
            'company_site_id' => $site->id,
            'actor_id' => $record->created_by ?? null,
            'action_key' => $actionKey,
            'module_key' => $moduleKey,
            'subject_type' => $record::class,
            'subject_id' => $record->getKey(),
            'subject_reference' => $record->reference ?? null,
            'icon' => $icon,
            'title' => $title,
            'message' => self::message($record, $title),
            'occurred_at' => $date,
        ];
    }

    private static function message(Model $record, string $title): string
    {
        $subject = $record->title ?? $record->label ?? $record->name ?? $record->reference ?? __('main.accounting_dashboard');

        return trim($title.' : '.$subject);
    }
}
