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
use App\Models\ArchiveActivity;
use App\Models\ArchiveBox;
use App\Models\ArchiveCabinet;
use App\Models\ArchiveCompartment;
use App\Models\ArchiveContainer;
use App\Models\ArchiveMovement;
use App\Models\ArchiveRack;
use App\Models\ArchiveRecord;
use App\Models\ArchiveRoom;
use App\Models\ArchiveShelf;
use App\Models\CompanySite;
use App\Models\DocumentManagementActivity;
use App\Models\DocumentManagementRecord;
use App\Models\HumanResourceContract;
use App\Models\HumanResourceEmployee;
use App\Models\HumanResourceLeaveRequest;
use App\Models\HumanResourcePayrollEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AccountingActivityFeed
{
    public static function forSite(CompanySite $site, ?User $user = null, int $limit = 10, ?string $moduleGroup = null): array
    {
        self::syncSite($site, $moduleGroup);

        return AccountingNotification::query()
            ->with(['actor:id,name,email', 'reads' => fn ($query) => $user ? $query->where('user_id', $user->id) : $query->whereRaw('1 = 0')])
            ->where('company_site_id', $site->id)
            ->when($moduleGroup, fn ($query) => $query->whereIn('module_key', self::moduleKeys($moduleGroup)))
            ->latest('occurred_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AccountingNotification $notification): array => self::present($notification, $user))
            ->all();
    }

    public static function unreadCount(CompanySite $site, User $user, ?string $moduleGroup = null): int
    {
        self::syncSite($site, $moduleGroup);

        return AccountingNotification::query()
            ->where('company_site_id', $site->id)
            ->when($moduleGroup, fn ($query) => $query->whereIn('module_key', self::moduleKeys($moduleGroup)))
            ->whereDoesntHave('reads', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNotNull('read_at'))
            ->count();
    }

    public static function syncSite(CompanySite $site, ?string $moduleGroup = null): void
    {
        collect(self::syncPayloads($site, $moduleGroup))
            ->each(fn (array $payload): AccountingNotification => AccountingNotification::query()->updateOrCreate(
                [
                    'action_key' => $payload['action_key'],
                    'subject_type' => $payload['subject_type'],
                    'subject_id' => $payload['subject_id'],
                ],
                $payload,
            ));
    }

    public static function moduleKeys(?string $moduleGroup): array
    {
        return match ($moduleGroup) {
            CompanySite::MODULE_ACCOUNTING => [
                'sales-invoices',
                'purchases',
                'purchase-orders',
                'credit-notes',
                'expenses',
                'other-incomes',
                'payment-reminders',
                'tasks',
                'bank-reconciliations',
                'treasury',
            ],
            CompanySite::MODULE_HUMAN_RESOURCES => [
                'hr-employees',
                'hr-contracts',
                'hr-leave',
                'hr-payroll',
            ],
            CompanySite::MODULE_DOCUMENT_MANAGEMENT => [
                'ged-incoming',
                'ged-outgoing',
                'ged-internal',
                'ged-folders',
                'ged-assignments',
                'ged-validation',
                'ged-history',
            ],
            CompanySite::MODULE_ARCHIVING => [
                'archive-locations',
                'archive-containers',
                'archive-records',
                'archive-movements',
                'archive-retention',
                'archive-traceability',
            ],
            default => [],
        };
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
            ->when(
                in_array($modelClass, [HumanResourceContract::class, HumanResourceLeaveRequest::class, HumanResourcePayrollEntry::class], true),
                fn ($query) => $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery
                    ->where('company_site_id', $site->id)
                    ->whereNull('user_id')),
                fn ($query) => $query->where('company_site_id', $site->id)
            )
            ->latest($dateColumn)
            ->limit(8)
            ->get()
            ->map(fn (Model $record): array => self::payload($site, $record, $actionKey, $title, $moduleKey, $icon, $record->{$dateColumn}));
    }

    private static function syncPayloads(CompanySite $site, ?string $moduleGroup): Collection
    {
        $payloads = collect();

        if ($moduleGroup === null || $moduleGroup === CompanySite::MODULE_ACCOUNTING) {
            $payloads = $payloads
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
                ->concat(self::statusRecords(AccountingTask::class, $site, AccountingTask::STATUS_COMPLETED, 'completed_task', __('main.activity_completed_task'), 'tasks', 'bi-check2-square'));
        }

        if ($moduleGroup === null || $moduleGroup === CompanySite::MODULE_HUMAN_RESOURCES) {
            $payloads = $payloads
                ->concat(self::records(HumanResourceEmployee::class, $site, 'added_hr_employee', __('main.activity_added_hr_employee'), 'hr-employees', 'bi-person-plus', 'created_at'))
                ->concat(self::records(HumanResourceContract::class, $site, 'added_hr_contract', __('main.activity_added_hr_contract'), 'hr-contracts', 'bi-file-earmark-text', 'created_at'))
                ->concat(self::records(HumanResourceLeaveRequest::class, $site, 'added_hr_leave', __('main.activity_added_hr_leave'), 'hr-leave', 'bi-calendar-check', 'created_at'))
                ->concat(self::records(HumanResourcePayrollEntry::class, $site, 'added_hr_payroll', __('main.activity_added_hr_payroll'), 'hr-payroll', 'bi-cash-stack', 'created_at'));
        }

        if ($moduleGroup === null || $moduleGroup === CompanySite::MODULE_DOCUMENT_MANAGEMENT) {
            $payloads = $payloads->concat(self::documentManagementActivities($site));
        }

        if ($moduleGroup === null || $moduleGroup === CompanySite::MODULE_ARCHIVING) {
            $payloads = $payloads->concat(self::archiveActivities($site));
        }

        return $payloads;
    }

    private static function documentManagementActivities(CompanySite $site): Collection
    {
        return DocumentManagementActivity::query()
            ->with(['actor:id,name,email', 'record:id,company_site_id,reference,record_type,subject'])
            ->whereHas('record', fn ($query) => $query->where('company_site_id', $site->id))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (DocumentManagementActivity $activity): array => [
                'company_site_id' => $site->id,
                'actor_id' => $activity->actor_id,
                'action_key' => 'ged_'.$activity->action,
                'module_key' => self::documentManagementModuleKey($activity),
                'subject_type' => DocumentManagementActivity::class,
                'subject_id' => $activity->id,
                'subject_reference' => $activity->record?->reference,
                'icon' => self::documentManagementActivityIcon($activity->action),
                'title' => self::documentManagementActivityTitle($activity),
                'message' => trim(($activity->comment ?: self::documentManagementActivityTitle($activity)).($activity->record?->subject ? ' : '.$activity->record->subject : '')),
                'occurred_at' => $activity->created_at ?: now(),
            ]);
    }

    private static function archiveActivities(CompanySite $site): Collection
    {
        return ArchiveActivity::query()
            ->with('actor:id,name,email')
            ->where('company_site_id', $site->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (ArchiveActivity $activity) use ($site): array {
                $subject = self::archiveActivitySubject($activity);

                return [
                    'company_site_id' => $site->id,
                    'actor_id' => $activity->actor_id,
                    'action_key' => 'archive_'.$activity->action,
                    'module_key' => self::archiveModuleKey($activity),
                    'subject_type' => ArchiveActivity::class,
                    'subject_id' => $activity->id,
                    'subject_reference' => $subject?->reference ?? $subject?->code ?? null,
                    'icon' => self::archiveActivityIcon($activity->action),
                    'title' => self::archiveActivityTitle($activity),
                    'message' => trim(($activity->comment ?: self::archiveActivityTitle($activity)).($subject?->name || $subject?->title ? ' : '.($subject->name ?? $subject->title) : '')),
                    'occurred_at' => $activity->created_at ?: now(),
                ];
            });
    }

    private static function documentManagementModuleKey(DocumentManagementActivity $activity): string
    {
        if (str_starts_with($activity->action, 'validation_')) {
            return 'ged-validation';
        }

        if ($activity->action === 'assignment_updated') {
            return 'ged-assignments';
        }

        return match ($activity->record?->record_type) {
            DocumentManagementRecord::TYPE_INCOMING => 'ged-incoming',
            DocumentManagementRecord::TYPE_OUTGOING => 'ged-outgoing',
            DocumentManagementRecord::TYPE_INTERNAL => 'ged-internal',
            default => 'ged-history',
        };
    }

    private static function documentManagementActivityIcon(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'validation_') => 'bi-check2-square',
            $action === 'assignment_updated' => 'bi-person-check',
            $action === 'status_changed' => 'bi-arrow-repeat',
            default => 'bi-file-earmark-text',
        };
    }

    private static function documentManagementActivityTitle(DocumentManagementActivity $activity): string
    {
        if ($activity->comment) {
            return $activity->comment;
        }

        return match ($activity->action) {
            'registered' => __('main.activity_ged_registered'),
            'updated' => __('main.activity_ged_updated'),
            'assignment_updated' => __('main.activity_ged_assignment_updated'),
            'status_changed' => __('main.activity_ged_status_changed'),
            'validation_started' => __('main.activity_ged_validation_started'),
            'validation_step_approved' => __('main.activity_ged_validation_step_approved'),
            'validation_approved' => __('main.activity_ged_validation_approved'),
            'validation_rejected' => __('main.activity_ged_validation_rejected'),
            default => __('main.activity_ged_updated'),
        };
    }

    private static function archiveModuleKey(ArchiveActivity $activity): string
    {
        return match ($activity->action) {
            'location_created', 'location_updated', 'location_deleted' => 'archive-locations',
            'container_created', 'container_updated', 'container_deleted', 'container_moved' => 'archive-containers',
            'record_archived', 'record_updated', 'record_file_attached', 'record_file_replaced' => 'archive-records',
            'record_moved' => 'archive-movements',
            default => 'archive-traceability',
        };
    }

    private static function archiveActivityIcon(string $action): string
    {
        return match ($action) {
            'location_created', 'location_updated', 'location_deleted' => 'bi-geo-alt',
            'container_created', 'container_updated', 'container_deleted', 'container_moved' => 'bi-folder2-open',
            'record_archived' => 'bi-archive',
            'record_updated' => 'bi-pencil-square',
            'record_file_attached' => 'bi-paperclip',
            'record_file_replaced' => 'bi-arrow-repeat',
            'record_moved' => 'bi-arrow-left-right',
            default => 'bi-clock-history',
        };
    }

    private static function archiveActivityTitle(ArchiveActivity $activity): string
    {
        if ($activity->comment) {
            return $activity->comment;
        }

        return match ($activity->action) {
            'location_created' => __('main.activity_archive_location_created'),
            'location_updated' => __('main.activity_archive_location_updated'),
            'location_deleted' => __('main.activity_archive_location_deleted'),
            'container_created' => __('main.activity_archive_container_created'),
            'container_updated' => __('main.activity_archive_container_updated'),
            'container_deleted' => __('main.activity_archive_container_deleted'),
            'record_archived' => __('main.activity_archive_record_archived'),
            'record_updated' => __('main.activity_archive_record_updated'),
            'record_file_attached' => __('main.activity_archive_record_file_attached'),
            'record_file_replaced' => __('main.activity_archive_record_file_replaced'),
            'record_moved' => __('main.activity_archive_record_moved'),
            'container_moved' => __('main.activity_archive_container_moved'),
            default => __('main.activity_archive_traceability'),
        };
    }

    private static function archiveActivitySubject(ArchiveActivity $activity): ?Model
    {
        if (! is_string($activity->subject_type) || ! class_exists($activity->subject_type)) {
            return null;
        }

        if (! is_subclass_of($activity->subject_type, Model::class)) {
            return null;
        }

        return $activity->subject_type::query()->find($activity->subject_id);
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
            'subject_reference' => $record->reference ?? $record->employee_number ?? $record->code ?? null,
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
