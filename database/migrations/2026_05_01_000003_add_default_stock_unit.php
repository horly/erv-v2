<?php

use App\Models\AccountingStockCategory;
use App\Models\AccountingStockUnit;
use App\Models\CompanySite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_stock_units', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('status');
            $table->index(['company_site_id', 'is_default'], 'acct_stock_unit_site_default_idx');
        });

        CompanySite::query()
            ->get()
            ->filter(fn (CompanySite $site) => in_array(CompanySite::MODULE_ACCOUNTING, $site->modules ?? [], true))
            ->each(function (CompanySite $site): void {
                AccountingStockUnit::query()->firstOrCreate(
                    [
                        'company_site_id' => $site->id,
                        'is_default' => true,
                    ],
                    [
                        'created_by' => $site->responsible_id,
                        'name' => 'Pièce',
                        'symbol' => 'pc',
                        'type' => AccountingStockUnit::TYPE_QUANTITY,
                        'status' => AccountingStockCategory::STATUS_ACTIVE,
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::table('accounting_stock_units', function (Blueprint $table) {
            $table->dropIndex('acct_stock_unit_site_default_idx');
            $table->dropColumn('is_default');
        });
    }
};
