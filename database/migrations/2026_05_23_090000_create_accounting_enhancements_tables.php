<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== Cost Centers ====================
        if (!Schema::hasTable('cost_centers')) Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['branch', 'department', 'project', 'other'])->default('other');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('level')->default(0);
            $table->string('path')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'type']);
            $table->index('parent_id');
        });

        // ==================== Tax Rates ====================
        if (!Schema::hasTable('tax_rates')) Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('rate', 8, 4)->default(0);
            $table->enum('type', ['sales', 'purchase', 'withholding', 'exempt', 'zero_rated'])->default('sales');
            $table->enum('calculation', ['percentage', 'fixed'])->default('percentage');
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_inclusive')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->jsonb('config_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'type', 'is_active']);
        });

        // ==================== Tax Templates ====================
        if (!Schema::hasTable('tax_templates')) Schema::create('tax_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('applies_to', ['sales', 'purchase', 'both'])->default('both');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        if (!Schema::hasTable('tax_template_lines')) Schema::create('tax_template_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();

            $table->unique(['tax_template_id', 'tax_rate_id']);
        });

        // ==================== Journal Batches ====================
        if (!Schema::hasTable('journal_batches')) Schema::create('journal_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 50);
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['manual', 'auto', 'adjustment', 'closing', 'opening', 'reversal'])->default('manual');
            $table->enum('status', ['draft', 'pending', 'posted', 'voided'])->default('draft');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('reversed_batch_id')->nullable()->constrained('journal_batches')->nullOnDelete();
            $table->string('currency_code', 3)->default('SAR');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'date']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'type']);
            $table->index(['source_type', 'source_id']);
        });

        // ==================== Journal Lines ====================
        if (!Schema::hasTable('journal_lines')) Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('journal_batches')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debit_fc', 15, 2)->nullable();
            $table->decimal('credit_fc', 15, 2)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->string('reference')->nullable();
            $table->date('due_date')->nullable();
            $table->jsonb('dimensions_json')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'line_number']);
            $table->index('account_id');
            $table->index('cost_center_id');
            $table->index('party_id');
        });

        // ==================== Add cost_center_id to existing tables ====================
        if (!Schema::hasColumn('invoices', 'cost_center_id')) Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
        });

        if (!Schema::hasColumn('payments', 'cost_center_id')) Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_center_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_center_id');
        });

        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_batches');
        Schema::dropIfExists('tax_template_lines');
        Schema::dropIfExists('tax_templates');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('cost_centers');
    }
};
