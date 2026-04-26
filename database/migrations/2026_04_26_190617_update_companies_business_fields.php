<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('rccm')->nullable()->after('name');
            $table->string('id_nat')->nullable()->after('rccm');
            $table->string('nif')->nullable()->after('id_nat');
            $table->string('website')->nullable()->after('nif');
            $table->string('slogan')->nullable()->after('website');
            $table->string('country')->default('Congo (RDC)')->after('slogan');
            $table->string('logo')->nullable()->after('country');
        });

        DB::table('companies')
            ->whereNull('email')
            ->orWhere('email', '')
            ->select('id')
            ->orderBy('id')
            ->each(function (object $company): void {
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['email' => 'company-'.$company->id.'@erp.loc']);
            });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });

        Schema::create('company_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 50);
            $table->string('label')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('company_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('account_number', 100);
            $table->string('bank_name')->nullable();
            $table->string('currency', 12)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_accounts');
        Schema::dropIfExists('company_phones');

        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->dropColumn([
                'rccm',
                'id_nat',
                'nif',
                'website',
                'slogan',
                'country',
                'logo',
            ]);
        });
    }
};