<?php

use App\Models\AccountingClient;
use App\Models\AccountingDebtor;
use App\Models\AccountingSalesInvoice;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_payment_reminders', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingSalesInvoice::class, 'sales_invoice_id')->nullable()->constrained('accounting_sales_invoices')->nullOnDelete();
            $table->foreignIdFor(AccountingDebtor::class, 'debtor_id')->nullable()->constrained('accounting_debtors')->nullOnDelete();
            $table->foreignIdFor(AccountingClient::class, 'client_id')->nullable()->constrained('accounting_clients')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('level', 30);
            $table->string('channel', 25);
            $table->string('status', 30)->default('sent');
            $table->string('subject');
            $table->text('message');
            $table->date('next_reminder_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique('sales_invoice_id', 'acct_payment_reminder_invoice_unique');
            $table->unique('debtor_id', 'acct_payment_reminder_debtor_unique');
            $table->index(['company_site_id', 'status'], 'acct_payment_reminder_site_status_idx');
            $table->index(['company_site_id', 'next_reminder_date'], 'acct_payment_reminder_site_next_idx');
        });

        Schema::create('accounting_payment_reminder_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_reminder_id')->constrained('accounting_payment_reminders')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 30);
            $table->string('channel', 25)->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->date('next_reminder_date')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index(['payment_reminder_id', 'action_at'], 'acct_payment_reminder_action_date_idx');
        });

        Schema::create('accounting_payment_promises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_reminder_id')->constrained('accounting_payment_reminders')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->date('promised_date');
            $table->string('status', 25)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payment_reminder_id', 'status'], 'acct_payment_promise_status_idx');
            $table->index(['promised_date', 'status'], 'acct_payment_promise_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payment_promises');
        Schema::dropIfExists('accounting_payment_reminder_actions');
        Schema::dropIfExists('accounting_payment_reminders');
    }
};
