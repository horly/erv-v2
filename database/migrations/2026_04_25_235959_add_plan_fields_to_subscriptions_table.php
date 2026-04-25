<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('type')->default('standard')->after('code');
            $table->unsignedInteger('company_limit')->nullable()->after('type');
            $table->date('expires_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['type', 'company_limit', 'expires_at']);
        });
    }
};
