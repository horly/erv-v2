<?php

use App\Models\AccountingStockCategory;
use App\Models\AccountingStockSubcategory;
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
            $table->boolean('is_default')->default(false)->after('status');
            $table->index(['company_site_id', 'is_default'], 'acct_stock_cat_site_default_idx');
        });

        Schema::table('accounting_stock_subcategories', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('status');
            $table->index(['company_site_id', 'is_default'], 'acct_stock_subcat_site_default_idx');
        });

        Schema::table('accounting_stock_warehouses', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('status');
            $table->index(['company_site_id', 'is_default'], 'acct_stock_wh_site_default_idx');
        });

        CompanySite::query()
            ->get()
            ->filter(fn (CompanySite $site) => in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true))
            ->each(function (CompanySite $site): void {
                $category = AccountingStockCategory::query()->firstOrCreate(
                    [
                        'company_site_id' => $site->id,
                        'is_default' => true,
                    ],
                    [
                        'created_by' => $site->responsible_id,
                        'name' => 'Categorie generale',
                        'description' => 'Categorie stock creee automatiquement par le systeme.',
                        'status' => AccountingStockCategory::STATUS_ACTIVE,
                    ]
                );

                AccountingStockSubcategory::query()->firstOrCreate(
                    [
                        'company_site_id' => $site->id,
                        'is_default' => true,
                    ],
                    [
                        'category_id' => $category->id,
                        'created_by' => $site->responsible_id,
                        'name' => 'Sous-categorie generale',
                        'description' => 'Sous-categorie stock creee automatiquement par le systeme.',
                        'status' => AccountingStockCategory::STATUS_ACTIVE,
                    ]
                );

                AccountingStockWarehouse::query()->firstOrCreate(
                    [
                        'company_site_id' => $site->id,
                        'is_default' => true,
                    ],
                    [
                        'created_by' => $site->responsible_id,
                        'name' => 'Entrepot principal',
                        'code' => 'DEP-DEFAULT',
                        'status' => AccountingStockCategory::STATUS_ACTIVE,
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::table('accounting_stock_warehouses', function (Blueprint $table) {
            $table->dropIndex('acct_stock_wh_site_default_idx');
            $table->dropColumn('is_default');
        });

        Schema::table('accounting_stock_subcategories', function (Blueprint $table) {
            $table->dropIndex('acct_stock_subcat_site_default_idx');
            $table->dropColumn('is_default');
        });

        Schema::table('accounting_stock_categories', function (Blueprint $table) {
            $table->dropIndex('acct_stock_cat_site_default_idx');
            $table->dropColumn('is_default');
        });
    }
};
