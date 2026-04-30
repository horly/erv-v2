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
        Schema::create('accounting_prospects', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_client_id')->nullable()->constrained('accounting_clients')->nullOnDelete();
            $table->string('type', 30);
            $table->string('name');
            $table->string('profession')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('rccm')->nullable();
            $table->string('id_nat')->nullable();
            $table->string('nif')->nullable();
            $table->string('website')->nullable();
            $table->string('source', 50)->default('other');
            $table->string('status', 50)->default('new');
            $table->string('interest_level', 30)->default('warm');
            $table->text('notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'type']);
            $table->index(['company_site_id', 'status']);
            $table->index(['company_site_id', 'interest_level']);
            $table->index(['company_site_id', 'name']);
        });

        Schema::create('accounting_prospect_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_prospect_id')->constrained('accounting_prospects')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();

            $table->index(['accounting_prospect_id', 'full_name'], 'acct_prospect_contacts_prospect_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_prospect_contacts');
        Schema::dropIfExists('accounting_prospects');
    }
};
