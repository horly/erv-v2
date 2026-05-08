<?php

use App\Models\AccountingClient;
use App\Models\AccountingCustomerOrder;
use App\Models\AccountingDeliveryNote;
use App\Models\AccountingPaymentMethod;
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
        Schema::create('accounting_sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingClient::class, 'client_id')->constrained('accounting_clients')->cascadeOnDelete();
            $table->foreignIdFor(AccountingCustomerOrder::class, 'customer_order_id')->nullable()->constrained('accounting_customer_orders')->nullOnDelete();
            $table->foreignIdFor(AccountingDeliveryNote::class, 'delivery_note_id')->nullable()->constrained('accounting_delivery_notes')->nullOnDelete();
            $table->foreignIdFor(AccountingProformaInvoice::class, 'proforma_invoice_id')->nullable()->constrained('accounting_proforma_invoices')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency', 3);
            $table->string('status', 30)->default('draft');
            $table->string('payment_terms', 30)->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('total_ht', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_ttc', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_sales_invoice_site_status_idx');
            $table->index(['company_site_id', 'invoice_date'], 'acct_sales_invoice_site_date_idx');
            $table->index(['company_site_id', 'client_id'], 'acct_sales_invoice_site_client_idx');
        });

        Schema::create('accounting_sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('accounting_sales_invoices')->cascadeOnDelete();
            $table->string('line_type', 30);
            $table->foreignIdFor(AccountingStockItem::class, 'item_id')->nullable()->constrained('accounting_stock_items')->nullOnDelete();
            $table->foreignIdFor(AccountingService::class, 'service_id')->nullable()->constrained('accounting_services')->nullOnDelete();
            $table->foreignId('customer_order_line_id')->nullable()->constrained('accounting_customer_order_lines')->nullOnDelete();
            $table->foreignId('delivery_note_line_id')->nullable()->constrained('accounting_delivery_note_lines')->nullOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['sales_invoice_id', 'line_type'], 'acct_sales_invoice_line_type_idx');
        });

        Schema::create('accounting_sales_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('accounting_sales_invoices')->cascadeOnDelete();
            $table->foreignIdFor(AccountingPaymentMethod::class, 'payment_method_id')->nullable()->constrained('accounting_payment_methods')->nullOnDelete();
            $table->foreignIdFor(User::class, 'received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sales_invoice_id', 'payment_date'], 'acct_sales_invoice_payment_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_sales_invoice_payments');
        Schema::dropIfExists('accounting_sales_invoice_lines');
        Schema::dropIfExists('accounting_sales_invoices');
    }
};
