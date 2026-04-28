<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type', 30);
            $table->string('code')->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->json('modules');
            $table->string('currency', 3);
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_sites');
    }
};
