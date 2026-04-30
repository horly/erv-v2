<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_clients', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('nif');
            $table->string('currency', 3)->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_clients', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'currency']);
        });
    }
};
