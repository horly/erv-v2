<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function accountingRecurringServices(): HasMany
    {
        return $this->hasMany(AccountingRecurringService::class);
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
            self::MODULE_ARCHIVING,
            self::MODULE_DOCUMENT_MANAGEMENT,
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
