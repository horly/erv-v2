<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_payment_methods', function (Blueprint $table): void {
            $table->timestamp('cash_tracking_started_at')->nullable()->after('is_system_default');
        });

        // Les encaissements et décaissements antérieurs à l'activation du suivi
        // de caisse ne doivent pas entrer dans le solde : on fixe le point de départ
        // à la date de déploiement pour les modes de paiement existants.
        DB::table('accounting_payment_methods')->update([
            'cash_tracking_started_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('accounting_payment_methods', function (Blueprint $table): void {
            $table->dropColumn('cash_tracking_started_at');
        });
    }
};
