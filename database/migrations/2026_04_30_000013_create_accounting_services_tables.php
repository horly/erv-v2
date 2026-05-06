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
        Schema::create('accounting_service_units', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('symbol', 30);
            $table->string('status', 20)->default('active');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_srv_unit_site_name_idx');
            $table->index(['company_site_id', 'is_default'], 'acct_srv_unit_site_default_idx');
        });

        Schema::create('accounting_service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_srv_cat_site_name_idx');
            $table->index(['company_site_id', 'is_default'], 'acct_srv_cat_site_default_idx');
        });

        Schema::create('accounting_service_subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('accounting_service_categories')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['company_site_id', 'category_id'], 'acct_srv_subcat_site_cat_idx');
            $table->index(['company_site_id', 'is_default'], 'acct_srv_subcat_site_default_idx');
        });

        Schema::create('accounting_services', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('accounting_service_categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('accounting_service_subcategories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('accounting_service_units')->restrictOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('billing_type', 30)->default('fixed');
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency', 3)->default('CDF');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedInteger('estimated_duration')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'name'], 'acct_srv_site_name_idx');
            $table->index(['company_site_id', 'category_id'], 'acct_srv_site_cat_idx');
            $table->index(['company_site_id', 'status'], 'acct_srv_site_status_idx');
        });

        Schema::create('accounting_recurring_services', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('accounting_services')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('frequency', 30)->default('monthly');
            $table->date('start_date')->nullable();
            $table->date('next_invoice_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'frequency'], 'acct_rec_srv_site_freq_idx');
            $table->index(['company_site_id', 'status'], 'acct_rec_srv_site_status_idx');
        });

        $this->createDefaultsForAccountingSites();
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_recurring_services');
        Schema::dropIfExists('accounting_services');
        Schema::dropIfExists('accounting_service_subcategories');
        Schema::dropIfExists('accounting_service_categories');
        Schema::dropIfExists('accounting_service_units');
    }

    private function createDefaultsForAccountingSites(): void
    {
        DB::table('company_sites')
            ->select(['id', 'responsible_id', 'modules'])
            ->orderBy('id')
            ->get()
            ->filter(fn ($site) => in_array(CompanySite::MODULE_ACCOUNTING, json_decode($site->modules ?: '[]', true) ?: [], true))
            ->each(function ($site): void {
                $now = now();

                $unitId = DB::table('accounting_service_units')->insertGetId([
                    'reference' => null,
                    'company_site_id' => $site->id,
                    'created_by' => $site->responsible_id,
                    'name' => 'Forfait',
                    'symbol' => 'forfait',
                    'status' => 'active',
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('accounting_service_units')->where('id', $unitId)->update(['reference' => 'SUN-'.str_pad((string) $unitId, 6, '0', STR_PAD_LEFT)]);

                $categoryId = DB::table('accounting_service_categories')->insertGetId([
                    'reference' => null,
                    'company_site_id' => $site->id,
                    'created_by' => $site->responsible_id,
                    'name' => 'Services generaux',
                    'description' => 'Categorie de services creee automatiquement par le systeme.',
                    'status' => 'active',
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('accounting_service_categories')->where('id', $categoryId)->update(['reference' => 'SCA-'.str_pad((string) $categoryId, 6, '0', STR_PAD_LEFT)]);

                $subcategoryId = DB::table('accounting_service_subcategories')->insertGetId([
                    'reference' => null,
                    'company_site_id' => $site->id,
                    'category_id' => $categoryId,
                    'created_by' => $site->responsible_id,
                    'name' => 'Prestations generales',
                    'description' => 'Sous-categorie de services creee automatiquement par le systeme.',
                    'status' => 'active',
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('accounting_service_subcategories')->where('id', $subcategoryId)->update(['reference' => 'SSC-'.str_pad((string) $subcategoryId, 6, '0', STR_PAD_LEFT)]);
            });
    }
};
