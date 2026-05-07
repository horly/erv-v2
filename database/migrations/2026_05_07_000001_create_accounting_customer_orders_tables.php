<?php

use App\Models\AccountingClient;
use App\Models\AccountingProformaInvoice;
use App\Models\AccountingService;
use App\Models\AccountingStockItem;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingClient::class, 'client_id')->constrained('accounting_clients')->cascadeOnDelete();
            $table->foreignIdFor(AccountingProformaInvoice::class, 'proforma_invoice_id')->nullable()->constrained('accounting_proforma_invoices')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('currency', 3);
            $table->string('status', 30)->default('draft');
            $table->string('payment_terms', 30)->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('cost_total', 18, 2)->default(0);
            $table->decimal('margin_total', 18, 2)->default(0);
            $table->decimal('margin_rate', 8, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('total_ht', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_ttc', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_customer_order_site_status_idx');
            $table->index(['company_site_id', 'order_date'], 'acct_customer_order_site_date_idx');
            $table->index(['company_site_id', 'client_id'], 'acct_customer_order_site_client_idx');
        });

        Schema::create('accounting_customer_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_id')->constrained('accounting_customer_orders')->cascadeOnDelete();
            $table->string('line_type', 30);
            $table->foreignIdFor(AccountingStockItem::class, 'item_id')->nullable()->constrained('accounting_stock_items')->nullOnDelete();
            $table->foreignIdFor(AccountingService::class, 'service_id')->nullable()->constrained('accounting_services')->nullOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->string('margin_type', 20)->default('fixed');
            $table->decimal('margin_value', 18, 2)->default(0);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('cost_total', 18, 2)->default(0);
            $table->decimal('margin_total', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['customer_order_id', 'line_type'], 'acct_customer_order_line_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_customer_order_lines');
        Schema::dropIfExists('accounting_customer_orders');
    }
};
