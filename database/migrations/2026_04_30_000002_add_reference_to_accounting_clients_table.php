<?php

use App\Models\AccountingClient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_clients', function (Blueprint $table) {
            $table->string('reference', 30)->nullable()->after('id');
            $table->unique('reference');
        });

        DB::table('accounting_clients')
            ->orderBy('id')
            ->select('id')
            ->lazy()
            ->each(function ($client): void {
                DB::table('accounting_clients')
                    ->where('id', $client->id)
                    ->update(['reference' => AccountingClient::referenceFromId((int) $client->id)]);
            });
    }

    public function down(): void
    {
        Schema::table('accounting_clients', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }
};
