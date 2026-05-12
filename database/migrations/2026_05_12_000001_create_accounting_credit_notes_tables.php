<?php

use App\Models\AccountingClient;
use App\Models\AccountingSalesInvoice;
use App\Models\AccountingSalesInvoiceLine;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingSalesInvoice::class, 'sales_invoice_id')->constrained('accounting_sales_invoices')->cascadeOnDelete();
            $table->foreignIdFor(AccountingClient::class, 'client_id')->constrained('accounting_clients')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('credit_date');
            $table->string('currency', 3);
            $table->string('status', 30)->default('draft');
            $table->text('reason')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_ttc', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_credit_note_site_status_idx');
            $table->index(['company_site_id', 'credit_date'], 'acct_credit_note_site_date_idx');
            $table->index(['sales_invoice_id', 'status'], 'acct_credit_note_invoice_status_idx');
        });

        Schema::create('accounting_credit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained('accounting_credit_notes')->cascadeOnDelete();
            $table->foreignIdFor(AccountingSalesInvoiceLine::class, 'sales_invoice_line_id')->nullable()->constrained('accounting_sales_invoice_lines')->nullOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['credit_note_id', 'sales_invoice_line_id'], 'acct_credit_note_line_invoice_line_idx');
        });

        Schema::table('accounting_sales_invoices', function (Blueprint $table) {
            $table->decimal('credit_total', 18, 2)->default(0)->after('paid_total');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_sales_invoices', function (Blueprint $table) {
            $table->dropColumn('credit_total');
        });

        Schema::dropIfExists('accounting_credit_note_lines');
        Schema::dropIfExists('accounting_credit_notes');
    }
};
