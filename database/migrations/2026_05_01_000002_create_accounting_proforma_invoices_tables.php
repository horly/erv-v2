<?php

use App\Models\AccountingClient;
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
        Schema::create('accounting_proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingClient::class, 'client_id')->constrained('accounting_clients')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('issue_date');
            $table->date('expiration_date')->nullable();
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
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_proforma_site_status_idx');
            $table->index(['company_site_id', 'issue_date'], 'acct_proforma_site_issue_idx');
            $table->index(['company_site_id', 'client_id'], 'acct_proforma_site_client_idx');
        });

        Schema::create('accounting_proforma_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')->constrained('accounting_proforma_invoices')->cascadeOnDelete();
            $table->string('line_type', 30);
            $table->foreignIdFor(AccountingStockItem::class, 'item_id')->nullable()->constrained('accounting_stock_items')->nullOnDelete();
            $table->foreignIdFor(AccountingService::class, 'service_id')->nullable()->constrained('accounting_services')->nullOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['proforma_invoice_id', 'line_type'], 'acct_proforma_line_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_proforma_invoice_lines');
        Schema::dropIfExists('accounting_proforma_invoices');
    }
};
