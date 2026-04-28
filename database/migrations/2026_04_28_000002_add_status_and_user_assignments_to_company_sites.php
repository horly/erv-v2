<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_sites', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('currency')->index();
        });

        Schema::create('company_site_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_site_id')->constrained('company_sites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_site_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_site_user');

        Schema::table('company_sites', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
