<?php

use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_stock_categories', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_stock_cat_site_name_idx');
            $table->index(['company_site_id', 'status'], 'acct_stock_cat_site_status_idx');
        });

        Schema::create('accounting_stock_subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('accounting_stock_categories')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_site_id', 'category_id'], 'acct_stock_subcat_site_cat_idx');
            $table->index(['company_site_id', 'name'], 'acct_stock_subcat_site_name_idx');
        });

        Schema::create('accounting_stock_units', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('type', 30)->default('unit');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_stock_unit_site_name_idx');
        });

        Schema::create('accounting_stock_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('code', 60)->nullable();
            $table->string('manager_name')->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_stock_wh_site_name_idx');
            $table->index(['company_site_id', 'status'], 'acct_stock_wh_site_status_idx');
        });

        Schema::create('accounting_stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('accounting_stock_categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('accounting_stock_subcategories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('accounting_stock_units')->restrictOnDelete();
            $table->foreignId('default_warehouse_id')->nullable()->constrained('accounting_stock_warehouses')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('type', 30)->default('product');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->decimal('min_stock', 15, 2)->default(0);
            $table->string('currency', 3)->default('CDF');
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_stock_item_site_name_idx');
            $table->index(['company_site_id', 'category_id'], 'acct_stock_item_site_cat_idx');
            $table->index(['company_site_id', 'status'], 'acct_stock_item_site_status_idx');
        });

        Schema::create('accounting_stock_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('accounting_stock_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('accounting_stock_warehouses')->restrictOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_site_id', 'item_id'], 'acct_stock_batch_site_item_idx');
            $table->index(['warehouse_id', 'item_id'], 'acct_stock_batch_wh_item_idx');
        });

        Schema::create('accounting_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('accounting_stock_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('accounting_stock_warehouses')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('accounting_stock_batches')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('quantity', 15, 2);
            $table->date('movement_date')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'type'], 'acct_stock_move_site_type_idx');
            $table->index(['company_site_id', 'item_id'], 'acct_stock_move_site_item_idx');
        });

        Schema::create('accounting_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('accounting_stock_items')->cascadeOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('accounting_stock_warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('accounting_stock_warehouses')->restrictOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->date('transfer_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_stock_transfer_site_status_idx');
            $table->index(['company_site_id', 'item_id'], 'acct_stock_transfer_site_item_idx');
        });

        Schema::create('accounting_stock_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('accounting_stock_warehouses')->restrictOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('counted_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_stock_inv_site_status_idx');
        });

        Schema::create('accounting_stock_inventory_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('accounting_stock_inventories')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('accounting_stock_items')->cascadeOnDelete();
            $table->decimal('expected_quantity', 15, 2)->default(0);
            $table->decimal('counted_quantity', 15, 2)->default(0);
            $table->decimal('difference_quantity', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_id', 'item_id'], 'acct_stock_inv_line_unique');
        });

        Schema::create('accounting_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('accounting_stock_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('accounting_stock_warehouses')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('low_stock');
            $table->decimal('threshold_quantity', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_stock_alert_site_status_idx');
            $table->index(['company_site_id', 'item_id'], 'acct_stock_alert_site_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_stock_alerts');
        Schema::dropIfExists('accounting_stock_inventory_lines');
        Schema::dropIfExists('accounting_stock_inventories');
        Schema::dropIfExists('accounting_stock_transfers');
        Schema::dropIfExists('accounting_stock_movements');
        Schema::dropIfExists('accounting_stock_batches');
        Schema::dropIfExists('accounting_stock_items');
        Schema::dropIfExists('accounting_stock_warehouses');
        Schema::dropIfExists('accounting_stock_units');
        Schema::dropIfExists('accounting_stock_subcategories');
        Schema::dropIfExists('accounting_stock_categories');
    }
};
