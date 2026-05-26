<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_key', 80);
            $table->string('module_key', 80)->default('dashboard');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_reference')->nullable();
            $table->string('icon', 80)->default('bi-bell');
            $table->string('title');
            $table->text('message')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->unique(['action_key', 'subject_type', 'subject_id'], 'acct_notifications_action_subject_unique');
            $table->index(['company_site_id', 'occurred_at']);
        });

        Schema::create('accounting_notification_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('accounting_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['accounting_notification_id', 'user_id'], 'acct_notification_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_notification_reads');
        Schema::dropIfExists('accounting_notifications');
    }
};
