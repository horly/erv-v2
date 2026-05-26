<?php

use App\Models\AccountingPaymentMethod;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_bank_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingPaymentMethod::class, 'payment_method_id')->constrained('accounting_payment_methods')->restrictOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('statement_opening_balance', 18, 2)->default(0);
            $table->decimal('statement_closing_balance', 18, 2)->default(0);
            $table->decimal('erp_closing_balance', 18, 2)->default(0);
            $table->decimal('difference', 18, 2)->default(0);
            $table->string('currency', 3);
            $table->string('status', 25)->default('in_progress');
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'period_end'], 'acct_bank_rec_site_period_idx');
            $table->index(['payment_method_id', 'status'], 'acct_bank_rec_method_status_idx');
        });

        Schema::create('accounting_bank_statement_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('accounting_bank_reconciliations')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('description');
            $table->string('direction', 20);
            $table->decimal('amount', 18, 2);
            $table->string('status', 20)->default('unmatched');
            $table->string('import_batch', 60)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bank_reconciliation_id', 'status'], 'acct_bank_line_rec_status_idx');
            $table->index(['bank_reconciliation_id', 'transaction_date'], 'acct_bank_line_rec_date_idx');
        });

        Schema::create('accounting_bank_reconciliation_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('statement_line_id')->constrained('accounting_bank_statement_lines')->cascadeOnDelete();
            $table->foreignId('treasury_movement_id');
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamp('matched_at');
            $table->timestamps();

            $table->foreign('treasury_movement_id', 'acct_bank_match_movement_fk')
                ->references('id')
                ->on('accounting_treasury_movements')
                ->restrictOnDelete();
            $table->unique('treasury_movement_id', 'acct_bank_match_unique_movement');
            $table->index('statement_line_id', 'acct_bank_match_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_bank_reconciliation_matches');
        Schema::dropIfExists('accounting_bank_statement_lines');
        Schema::dropIfExists('accounting_bank_reconciliations');
    }
};
