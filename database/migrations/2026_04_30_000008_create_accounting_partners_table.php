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
        Schema::create('accounting_partners', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->string('activity_domain')->nullable();
            $table->date('partnership_started_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_site_id', 'type']);
            $table->index(['company_site_id', 'name']);
            $table->index(['company_site_id', 'status']);
            $table->index(['company_site_id', 'activity_domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_partners');
    }
};
