<?php

use App\Models\AccountingStockCategory;
use App\Models\AccountingStockWarehouse;
use App\Models\CompanySite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_stock_categories', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('company_site_id')
                ->constrained('accounting_stock_warehouses')
                ->nullOnDelete();

            $table->index(['company_site_id', 'warehouse_id'], 'acct_stock_cat_site_wh_idx');
        });

        CompanySite::query()
            ->get()
            ->filter(fn (CompanySite $site) => in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true))
            ->each(function (CompanySite $site): void {
                $warehouse = AccountingStockWarehouse::query()->firstOrCreate(
                    [
                        'company_site_id' => $site->id,
                        'is_default' => true,
                    ],
                    [
                        'created_by' => $site->responsible_id,
                        'name' => 'Entrepot principal',
                        'code' => 'DEP-DEFAULT',
                        'status' => 'active',
                    ]
                );

                AccountingStockCategory::query()
                    ->where('company_site_id', $site->id)
                    ->where('is_default', true)
                    ->update(['warehouse_id' => $warehouse->id]);
            });
    }

    public function down(): void
    {
        Schema::table('accounting_stock_categories', function (Blueprint $table) {
            $table->dropIndex('acct_stock_cat_site_wh_idx');
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
