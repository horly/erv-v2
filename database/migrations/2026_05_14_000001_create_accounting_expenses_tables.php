<?php

use App\Models\CompanySite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_system_default')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['company_site_id', 'slug']);
            $table->index(['company_site_id', 'status']);
        });

        Schema::create('accounting_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('accounting_expense_categories')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('accounting_payment_methods')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable()->unique();
            $table->date('expense_date');
            $table->string('label');
            $table->string('beneficiary')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->string('payment_reference')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();

            $table->index(['company_site_id', 'status']);
            $table->index(['company_site_id', 'expense_date']);
            $table->index(['payment_method_id', 'status']);
        });

        $this->seedDefaultCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_expenses');
        Schema::dropIfExists('accounting_expense_categories');
    }

    private function seedDefaultCategories(): void
    {
        if (! Schema::hasTable('company_sites')) {
            return;
        }

        $defaults = [
            'loyer' => 'Loyer',
            'transport' => 'Transport',
            'carburant' => 'Carburant',
            'communication' => 'Communication',
            'internet' => 'Internet',
            'electricite' => 'Electricite',
            'eau' => 'Eau',
            'frais-bancaires' => 'Frais bancaires',
            'frais-administratifs' => 'Frais administratifs',
            'entretien' => 'Entretien et maintenance',
            'mission' => 'Mission et déplacement',
            'restauration' => 'Restauration',
            'avances-salaires' => 'Avances sur salaires',
            'taxes' => 'Taxes et impôts',
            'autres-charges' => 'Autres charges',
        ];

        CompanySite::query()->select('id')->chunkById(100, function ($sites) use ($defaults): void {
            foreach ($sites as $site) {
                foreach ($defaults as $slug => $name) {
                    DB::table('accounting_expense_categories')->updateOrInsert(
                        ['company_site_id' => $site->id, 'slug' => $slug],
                        [
                            'name' => $name,
                            'description' => null,
                            'is_system_default' => true,
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        });
    }
};
