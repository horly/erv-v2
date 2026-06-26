<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanySite extends Model
{
    use HasFactory;

    public const TYPE_PRODUCTION = 'production';
    public const TYPE_WAREHOUSE = 'warehouse';
    public const TYPE_OFFICE = 'office';
    public const TYPE_SHOP = 'shop';
    public const TYPE_ARCHIVE = 'archive';
    public const TYPE_OTHER = 'other';

    public const MODULE_ACCOUNTING = 'accounting';
    public const MODULE_HUMAN_RESOURCES = 'human_resources';
    public const MODULE_ARCHIVING = 'archiving';
    public const MODULE_DOCUMENT_MANAGEMENT = 'document_management';
    public const MODULE_GMAO = 'gmao';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_id',
        'responsible_id',
        'name',
        'type',
        'code',
        'city',
        'phone',
        'email',
        'address',
        'modules',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_site_user')
            ->withPivot(['module_permissions', 'can_create', 'can_update', 'can_delete'])
            ->withTimestamps();
    }

    public function accountingClients(): HasMany
    {
        return $this->hasMany(AccountingClient::class);
    }

    public function accountingSuppliers(): HasMany
    {
        return $this->hasMany(AccountingSupplier::class);
    }

    public function accountingProspects(): HasMany
    {
        return $this->hasMany(AccountingProspect::class);
    }

    public function accountingProformaInvoices(): HasMany
    {
        return $this->hasMany(AccountingProformaInvoice::class);
    }

    public function accountingCustomerOrders(): HasMany
    {
        return $this->hasMany(AccountingCustomerOrder::class);
    }

    public function accountingCreditors(): HasMany
    {
        return $this->hasMany(AccountingCreditor::class);
    }

    public function accountingDebtors(): HasMany
    {
        return $this->hasMany(AccountingDebtor::class);
    }

    public function accountingPartners(): HasMany
    {
        return $this->hasMany(AccountingPartner::class);
    }

    public function accountingSalesRepresentatives(): HasMany
    {
        return $this->hasMany(AccountingSalesRepresentative::class);
    }

    public function accountingCurrencies(): HasMany
    {
        return $this->hasMany(AccountingCurrency::class);
    }

    public function accountingPaymentMethods(): HasMany
    {
        return $this->hasMany(AccountingPaymentMethod::class);
    }

    public function accountingModuleSetting(): HasOne
    {
        return $this->hasOne(AccountingModuleSetting::class);
    }

    public function accountingMenuPermissions(): HasMany
    {
        return $this->hasMany(AccountingMenuPermission::class);
    }

    public function accountingTaxes(): HasMany
    {
        return $this->hasMany(AccountingTax::class);
    }

    public function accountingTreasuryMovements(): HasMany
    {
        return $this->hasMany(AccountingTreasuryMovement::class);
    }

    public function accountingBankReconciliations(): HasMany
    {
        return $this->hasMany(AccountingBankReconciliation::class);
    }

    public function accountingPaymentReminders(): HasMany
    {
        return $this->hasMany(AccountingPaymentReminder::class);
    }

    public function accountingOtherIncomes(): HasMany
    {
        return $this->hasMany(AccountingOtherIncome::class);
    }

    public function accountingExpenseCategories(): HasMany
    {
        return $this->hasMany(AccountingExpenseCategory::class);
    }

    public function accountingExpenses(): HasMany
    {
        return $this->hasMany(AccountingExpense::class);
    }

    public function accountingPurchases(): HasMany
    {
        return $this->hasMany(AccountingPurchase::class);
    }

    public function accountingPurchaseOrders(): HasMany
    {
        return $this->hasMany(AccountingPurchaseOrder::class);
    }

    public function accountingStockCategories(): HasMany
    {
        return $this->hasMany(AccountingStockCategory::class);
    }

    public function accountingStockSubcategories(): HasMany
    {
        return $this->hasMany(AccountingStockSubcategory::class);
    }

    public function accountingStockUnits(): HasMany
    {
        return $this->hasMany(AccountingStockUnit::class);
    }

    public function accountingStockItems(): HasMany
    {
        return $this->hasMany(AccountingStockItem::class);
    }

    public function accountingStockWarehouses(): HasMany
    {
        return $this->hasMany(AccountingStockWarehouse::class);
    }

    public function accountingStockBatches(): HasMany
    {
        return $this->hasMany(AccountingStockBatch::class);
    }

    public function accountingStockMovements(): HasMany
    {
        return $this->hasMany(AccountingStockMovement::class);
    }

    public function accountingDeliveryNotes(): HasMany
    {
        return $this->hasMany(AccountingDeliveryNote::class);
    }

    public function accountingSalesInvoices(): HasMany
    {
        return $this->hasMany(AccountingSalesInvoice::class);
    }

    public function accountingCreditNotes(): HasMany
    {
        return $this->hasMany(AccountingCreditNote::class);
    }

    public function accountingTasks(): HasMany
    {
        return $this->hasMany(AccountingTask::class);
    }

    public function accountingCashRegisterSessions(): HasMany
    {
        return $this->hasMany(AccountingCashRegisterSession::class);
    }

    public function accountingStockTransfers(): HasMany
    {
        return $this->hasMany(AccountingStockTransfer::class);
    }

    public function accountingStockInventories(): HasMany
    {
        return $this->hasMany(AccountingStockInventory::class);
    }

    public function accountingStockAlerts(): HasMany
    {
        return $this->hasMany(AccountingStockAlert::class);
    }

    public function accountingServiceUnits(): HasMany
    {
        return $this->hasMany(AccountingServiceUnit::class);
    }

    public function accountingServiceCategories(): HasMany
    {
        return $this->hasMany(AccountingServiceCategory::class);
    }

    public function accountingServiceSubcategories(): HasMany
    {
        return $this->hasMany(AccountingServiceSubcategory::class);
    }

    public function accountingServices(): HasMany
    {
        return $this->hasMany(AccountingService::class);
    }

    public function humanResourceDepartments(): HasMany
    {
        return $this->hasMany(HumanResourceDepartment::class);
    }

    public function humanResourceEmployees(): HasMany
    {
        return $this->hasMany(HumanResourceEmployee::class);
    }

    public function humanResourceContracts(): HasManyThrough
    {
        return $this->hasManyThrough(HumanResourceContract::class, HumanResourceEmployee::class);
    }

    public function humanResourceLeaveRequests(): HasManyThrough
    {
        return $this->hasManyThrough(HumanResourceLeaveRequest::class, HumanResourceEmployee::class);
    }

    public function humanResourceAttendances(): HasManyThrough
    {
        return $this->hasManyThrough(HumanResourceAttendance::class, HumanResourceEmployee::class);
    }

    public function accountingRecurringServices(): HasMany
    {
        return $this->hasMany(AccountingRecurringService::class);
    }

    public function documentManagementFolders(): HasMany
    {
        return $this->hasMany(DocumentManagementFolder::class);
    }

    public function documentManagementRecords(): HasMany
    {
        return $this->hasMany(DocumentManagementRecord::class);
    }

    public function documentManagementValidationCircuits(): HasMany
    {
        return $this->hasMany(DocumentManagementValidationCircuit::class);
    }

    public function archiveLocations(): HasMany
    {
        return $this->hasMany(ArchiveLocation::class);
    }

    public function archiveRooms(): HasMany
    {
        return $this->hasMany(ArchiveRoom::class);
    }

    public function archiveRacks(): HasMany
    {
        return $this->hasMany(ArchiveRack::class);
    }

    public function archiveCabinets(): HasMany
    {
        return $this->hasMany(ArchiveCabinet::class);
    }

    public function archiveShelves(): HasMany
    {
        return $this->hasMany(ArchiveShelf::class);
    }

    public function archiveCompartments(): HasMany
    {
        return $this->hasMany(ArchiveCompartment::class);
    }

    public function archiveBoxes(): HasMany
    {
        return $this->hasMany(ArchiveBox::class);
    }

    public function archiveContainers(): HasMany
    {
        return $this->hasMany(ArchiveContainer::class);
    }

    public function archiveRecords(): HasMany
    {
        return $this->hasMany(ArchiveRecord::class);
    }

    public function archiveMovements(): HasMany
    {
        return $this->hasMany(ArchiveMovement::class);
    }

    public function archiveRetentionRules(): HasMany
    {
        return $this->hasMany(ArchiveRetentionRule::class);
    }

    public function archiveActivities(): HasMany
    {
        return $this->hasMany(ArchiveActivity::class);
    }

    public function gmaoLocations(): HasMany
    {
        return $this->hasMany(GmaoLocation::class);
    }

    public function gmaoEquipment(): HasMany
    {
        return $this->hasMany(GmaoEquipment::class);
    }

    public function gmaoEquipmentCategories(): HasMany
    {
        return $this->hasMany(GmaoEquipmentCategory::class);
    }

    public function gmaoTechnicians(): HasMany
    {
        return $this->hasMany(GmaoTechnician::class);
    }

    public function gmaoWorkRequests(): HasMany
    {
        return $this->hasMany(GmaoWorkRequest::class);
    }

    public function gmaoWorkOrders(): HasMany
    {
        return $this->hasMany(GmaoWorkOrder::class);
    }

    public function gmaoSpareParts(): HasMany
    {
        return $this->hasMany(GmaoSparePart::class);
    }

    public function gmaoPreventivePlans(): HasMany
    {
        return $this->hasMany(GmaoPreventivePlan::class);
    }

    public function gmaoMaintenanceRoutes(): HasMany
    {
        return $this->hasMany(GmaoMaintenanceRoute::class);
    }

    public function gmaoInterventionReports(): HasMany
    {
        return $this->hasMany(GmaoInterventionReport::class);
    }

    public function gmaoActivities(): HasMany
    {
        return $this->hasMany(GmaoActivity::class);
    }

    public static function types(): array
    {
        return [
            self::TYPE_PRODUCTION,
            self::TYPE_WAREHOUSE,
            self::TYPE_OFFICE,
            self::TYPE_SHOP,
            self::TYPE_ARCHIVE,
            self::TYPE_OTHER,
        ];
    }

    public static function modules(): array
    {
        return [
            self::MODULE_ACCOUNTING,
            self::MODULE_HUMAN_RESOURCES,
            self::MODULE_DOCUMENT_MANAGEMENT,
            self::MODULE_ARCHIVING,
            self::MODULE_GMAO,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }
}
