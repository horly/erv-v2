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
        Schema::create('accounting_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type', 30);
            $table->string('currency_code', 3);
            $table->string('code', 60)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic_swift')->nullable();
            $table->text('bank_address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_pay_method_site_name_idx');
            $table->index(['company_site_id', 'type'], 'acct_pay_method_site_type_idx');
            $table->index(['company_site_id', 'is_default'], 'acct_pay_method_site_default_idx');
            $table->index(['company_site_id', 'is_system_default'], 'acct_pay_method_site_system_idx');
        });

        $this->createDefaultsForAccountingSites();
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payment_methods');
    }

    private function createDefaultsForAccountingSites(): void
    {
        DB::table('company_sites')
            ->select(['id', 'responsible_id', 'modules', 'currency'])
            ->orderBy('id')
            ->get()
            ->filter(fn ($site) => in_array(CompanySite::MODULE_ACCOUNTING, json_decode($site->modules ?: '[]', true) ?: [], true))
            ->each(function ($site): void {
                $now = now();

                $id = DB::table('accounting_payment_methods')->insertGetId([
                    'reference' => null,
                    'company_site_id' => $site->id,
                    'created_by' => $site->responsible_id,
                    'name' => 'Espèces',
                    'type' => 'cash',
                    'currency_code' => $site->currency ?: 'CDF',
                    'is_default' => true,
                    'is_system_default' => true,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('accounting_payment_methods')->where('id', $id)->update([
                    'reference' => 'PAY-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
                ]);
            });
    }
};
