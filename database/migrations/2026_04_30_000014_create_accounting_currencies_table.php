<?php

use App\Models\CompanySite;
use App\Models\User;
use App\Support\CurrencyCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 3);
            $table->string('name');
            $table->string('symbol', 20)->nullable();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['company_site_id', 'code'], 'acct_currency_site_code_unique');
            $table->index(['company_site_id', 'is_base'], 'acct_currency_site_base_idx');
            $table->index(['company_site_id', 'is_default'], 'acct_currency_site_default_idx');
        });

        $this->createDefaultsForAccountingSites();
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_currencies');
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
                $code = $site->currency ?: 'CDF';
                $currency = CurrencyCatalog::all()[$code] ?? null;
                $name = $currency['name_fr'] ?? $code;
                $symbol = $currency['symbol'] ?? null;

                $id = DB::table('accounting_currencies')->insertGetId([
                    'reference' => null,
                    'company_site_id' => $site->id,
                    'created_by' => $site->responsible_id,
                    'code' => $code,
                    'name' => $name,
                    'symbol' => $symbol,
                    'exchange_rate' => 1,
                    'is_base' => true,
                    'is_default' => true,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('accounting_currencies')->where('id', $id)->update([
                    'reference' => 'CUR-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
                ]);
            });
    }
};
