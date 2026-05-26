<?php

use App\Models\AccountingClient;
use App\Models\AccountingSupplier;
use App\Models\CompanySite;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignIdFor(CompanySite::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(AccountingClient::class, 'client_id')->nullable()->constrained('accounting_clients')->nullOnDelete();
            $table->foreignIdFor(AccountingSupplier::class, 'supplier_id')->nullable()->constrained('accounting_suppliers')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 30);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 25)->default('todo');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 50)->nullable();
            $table->string('source_label')->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->string('automation_key')->nullable();
            $table->timestamps();

            $table->unique(['company_site_id', 'automation_key'], 'acct_tasks_site_automation_unique');
            $table->index(['company_site_id', 'status'], 'acct_tasks_site_status_idx');
            $table->index(['company_site_id', 'due_date'], 'acct_tasks_site_due_idx');
            $table->index(['company_site_id', 'assigned_to'], 'acct_tasks_site_assignee_idx');
            $table->index(['company_site_id', 'priority'], 'acct_tasks_site_priority_idx');
        });

        Schema::create('accounting_task_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('accounting_task_id')->constrained('accounting_tasks')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 30);
            $table->string('from_status', 25)->nullable();
            $table->string('to_status', 25)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['accounting_task_id', 'created_at'], 'acct_task_activity_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_task_activities');
        Schema::dropIfExists('accounting_tasks');
    }
};
