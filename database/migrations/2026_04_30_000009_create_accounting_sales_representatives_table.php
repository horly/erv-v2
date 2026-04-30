<?php

use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_sales_representatives', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('sales_area')->nullable();
            $table->string('currency', 3)->default('CDF');
            $table->decimal('monthly_target', 15, 2)->default(0);
            $table->decimal('annual_target', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'type'], 'acct_sales_rep_site_type_idx');
            $table->index(['company_site_id', 'name'], 'acct_sales_rep_site_name_idx');
            $table->index(['company_site_id', 'status'], 'acct_sales_rep_site_status_idx');
            $table->index(['company_site_id', 'sales_area'], 'acct_sales_rep_site_area_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_sales_representatives');
    }
};
