<?php

use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('name');
            $table->string('profession')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('rccm')->nullable();
            $table->string('id_nat')->nullable();
            $table->string('nif')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('website')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_site_id', 'type']);
            $table->index(['company_site_id', 'name']);
            $table->index(['company_site_id', 'status']);
        });

        Schema::create('accounting_supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_supplier_id')->constrained('accounting_suppliers')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();

            $table->index(['accounting_supplier_id', 'full_name'], 'acct_supplier_contacts_supplier_name_idx');
        });

        DB::table('accounting_suppliers')
            ->orderBy('id')
            ->select('id')
            ->lazy()
            ->each(function ($supplier): void {
                DB::table('accounting_suppliers')
                    ->where('id', $supplier->id)
                    ->update(['reference' => 'FRS-'.str_pad((string) $supplier->id, 6, '0', STR_PAD_LEFT)]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_supplier_contacts');
        Schema::dropIfExists('accounting_suppliers');
    }
};
