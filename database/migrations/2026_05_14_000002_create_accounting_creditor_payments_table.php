<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_creditor_payments')) {
            $indexes = collect(DB::select('SHOW INDEX FROM accounting_creditor_payments'))->pluck('Key_name')->all();

            Schema::table('accounting_creditor_payments', function (Blueprint $table) use ($indexes): void {
                if (! in_array('acp_creditor_date_idx', $indexes, true)) {
                    $table->index(['creditor_id', 'payment_date'], 'acp_creditor_date_idx');
                }

                if (! in_array('acp_method_date_idx', $indexes, true)) {
                    $table->index(['payment_method_id', 'payment_date'], 'acp_method_date_idx');
                }
            });

            return;
        }

        Schema::create('accounting_creditor_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creditor_id')->constrained('accounting_creditors')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('accounting_payment_methods')->restrictOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['creditor_id', 'payment_date'], 'acp_creditor_date_idx');
            $table->index(['payment_method_id', 'payment_date'], 'acp_method_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_creditor_payments');
    }
};
