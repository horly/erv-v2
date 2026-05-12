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
        Schema::create('accounting_other_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountingPaymentMethod::class, 'payment_method_id')->nullable()->constrained('accounting_payment_methods')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('income_date');
            $table->string('type', 50);
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('payment_reference')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();

            $table->index(['company_site_id', 'status'], 'acct_other_income_site_status_idx');
            $table->index(['company_site_id', 'income_date'], 'acct_other_income_site_date_idx');
            $table->index(['payment_method_id', 'status'], 'acct_other_income_payment_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_other_incomes');
    }
};
