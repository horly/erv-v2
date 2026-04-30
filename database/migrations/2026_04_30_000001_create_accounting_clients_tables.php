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
        Schema::create('accounting_clients', function (Blueprint $table) {
            $table->id();
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
            $table->string('account_number')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'type']);
            $table->index(['company_site_id', 'name']);
        });

        Schema::create('accounting_client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_client_id')->constrained('accounting_clients')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();

            $table->index(['accounting_client_id', 'full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_client_contacts');
        Schema::dropIfExists('accounting_clients');
    }
};
