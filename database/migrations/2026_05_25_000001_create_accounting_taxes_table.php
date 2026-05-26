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
        Schema::create('accounting_taxes', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('kind', 30)->default('vat');
            $table->string('calculation_type', 20)->default('percentage');
            $table->decimal('value', 18, 2)->default(0);
            $table->string('nature', 20)->default('collected');
            $table->string('applies_to', 20)->default('both');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['company_site_id', 'code'], 'acct_tax_site_code_unique');
            $table->index(['company_site_id', 'status'], 'acct_tax_site_status_idx');
            $table->index(['company_site_id', 'is_default'], 'acct_tax_site_default_idx');
        });

        $this->createDefaultsForAccountingSites();
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_taxes');
    }

    private function createDefaultsForAccountingSites(): void
    {
        DB::table('company_sites')
            ->join('companies', 'companies.id', '=', 'company_sites.company_id')
            ->select(['company_sites.id', 'company_sites.responsible_id', 'company_sites.modules', 'companies.country'])
            ->orderBy('company_sites.id')
            ->get()
            ->filter(fn ($site) => in_array(CompanySite::MODULE_ACCOUNTING, json_decode($site->modules ?: '[]', true) ?: [], true))
            ->each(function ($site): void {
                $now = now();
                $rate = $this->countryVatRate($site->country);

                $vatId = DB::table('accounting_taxes')->insertGetId([
                    'reference' => null,
                    'company_site_id' => $site->id,
                    'created_by' => $site->responsible_id,
                    'code' => 'TVA',
                    'name' => 'TVA',
                    'kind' => 'vat',
                    'calculation_type' => 'percentage',
                    'value' => $rate,
                    'nature' => 'collected',
                    'applies_to' => 'both',
                    'description' => null,
                    'is_default' => true,
                    'is_system_default' => true,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('accounting_taxes')->where('id', $vatId)->update([
                    'reference' => 'TAX-'.str_pad((string) $vatId, 6, '0', STR_PAD_LEFT),
                ]);

                $exemptionId = DB::table('accounting_taxes')->insertGetId([
                    'reference' => null,
                    'company_site_id' => $site->id,
                    'created_by' => $site->responsible_id,
                    'code' => 'EXONERE',
                    'name' => 'Exonération',
                    'kind' => 'exemption',
                    'calculation_type' => 'percentage',
                    'value' => 0,
                    'nature' => 'collected',
                    'applies_to' => 'both',
                    'description' => null,
                    'is_default' => false,
                    'is_system_default' => false,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('accounting_taxes')->where('id', $exemptionId)->update([
                    'reference' => 'TAX-'.str_pad((string) $exemptionId, 6, '0', STR_PAD_LEFT),
                ]);
            });
    }

    private function countryVatRate(?string $country): float
    {
        if (blank($country)) {
            return 0.0;
        }

        foreach (config('countries') as $meta) {
            if (in_array($country, [$meta['iso'], $meta['name'], $meta['name_fr'], $meta['name_en']], true)) {
                return (float) $meta['vat_rate'];
            }
        }

        return 0.0;
    }
};
