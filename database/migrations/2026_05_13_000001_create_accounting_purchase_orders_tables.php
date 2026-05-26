<?php

use App\Models\AccountingPurchase;
use App\Models\AccountingService;
use App\Models\AccountingStockItem;
use App\Models\AccountingSupplier;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingSupplier::class, 'supplier_id')->constrained('accounting_suppliers')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(AccountingPurchase::class, 'purchase_id')->nullable()->constrained('accounting_purchases')->nullOnDelete();
            $table->string('supplier_reference')->nullable();
            $table->string('title')->nullable();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('currency', 3);
            $table->string('status', 30)->default('draft');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('total_ht', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_ttc', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_purchase_orders_site_status_idx');
            $table->index(['company_site_id', 'order_date'], 'acct_purchase_orders_site_date_idx');
            $table->index(['company_site_id', 'supplier_id'], 'acct_purchase_orders_supplier_idx');
        });

        Schema::create('accounting_purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('accounting_purchase_orders')->cascadeOnDelete();
            $table->string('line_type', 30);
            $table->foreignIdFor(AccountingStockItem::class, 'item_id')->nullable()->constrained('accounting_stock_items')->nullOnDelete();
            $table->foreignIdFor(AccountingService::class, 'service_id')->nullable()->constrained('accounting_services')->nullOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('received_quantity', 18, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['purchase_order_id', 'line_type'], 'acct_po_line_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_purchase_order_lines');
        Schema::dropIfExists('accounting_purchase_orders');
    }
};
