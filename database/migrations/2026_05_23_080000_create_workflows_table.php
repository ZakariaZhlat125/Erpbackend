<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('entity_type');
            $table->enum('trigger_on', ['created', 'updated', 'status_changed', 'manual'])->default('manual');
            $table->string('trigger_field')->nullable();
            $table->string('trigger_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_parallel')->default(false);
            $table->jsonb('config_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'entity_type']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('approver_type', ['user', 'role', 'field', 'hierarchy', 'any_of', 'all_of'])->default('role');
            $table->string('approver_value');
            $table->jsonb('condition_json')->nullable();
            $table->unsignedInteger('timeout_hours')->nullable();
            $table->enum('on_timeout', ['wait', 'auto_approve', 'auto_reject', 'escalate', 'notify'])->default('wait');
            $table->string('escalate_to')->nullable();
            $table->enum('on_reject', ['stop', 'restart', 'goto_step', 'notify_only'])->default('stop');
            $table->unsignedBigInteger('goto_step_id')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->boolean('allow_delegation')->default(true);
            $table->jsonb('notifications_json')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'order']);
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'cancelled', 'expired'])->default('pending');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('final_comments')->nullable();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['workflow_id', 'status']);
            $table->index('current_step_id');
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('step_id')->constrained('workflow_steps')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'delegated', 'skipped', 'expired'])->default('pending');
            $table->text('comments')->nullable();
            $table->foreignId('delegated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('delegation_reason')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();

            $table->index(['instance_id', 'step_id']);
            $table->index(['assigned_to', 'status']);
            $table->index('acted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
    }
};
