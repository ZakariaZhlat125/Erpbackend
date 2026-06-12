<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== Attendance Records ====================
        if (!Schema::hasTable('attendances')) Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('check_in_method', ['gps', 'fingerprint', 'qr', 'manual', 'ip', 'remote'])->nullable();
            $table->enum('check_out_method', ['gps', 'fingerprint', 'qr', 'manual', 'ip', 'remote'])->nullable();
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->string('check_in_ip')->nullable();
            $table->string('check_out_ip')->nullable();
            $table->string('check_in_device')->nullable();
            $table->string('check_out_device')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'leave', 'holiday', 'weekend'])->default('present');
            $table->integer('worked_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index(['organization_id', 'date']);
            $table->index(['organization_id', 'employee_id', 'date']);
            $table->index('status');
        });

        // ==================== Attendance Settings ====================
        if (!Schema::hasTable('attendance_settings')) Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->time('work_start_time')->default('08:00:00');
            $table->time('work_end_time')->default('17:00:00');
            $table->integer('grace_period_minutes')->default(15);
            $table->integer('standard_work_hours')->default(8);
            $table->boolean('allow_remote')->default(false);
            $table->boolean('require_gps')->default(false);
            $table->decimal('office_latitude', 10, 8)->nullable();
            $table->decimal('office_longitude', 11, 8)->nullable();
            $table->integer('geo_fence_radius')->default(100);
            $table->jsonb('allowed_ips')->nullable();
            $table->string('weekend_days')->default('friday,saturday');
            $table->timestamps();

            $table->unique(['organization_id', 'branch_id']);
        });

        // ==================== Salary Components ====================
        if (!Schema::hasTable('salary_components')) Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['earning', 'deduction'])->default('earning');
            $table->enum('category', ['basic', 'allowance', 'bonus', 'overtime', 'deduction', 'tax', 'insurance', 'loan'])->default('allowance');
            $table->enum('calculation', ['fixed', 'percentage', 'formula'])->default('fixed');
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->decimal('percentage', 8, 4)->nullable();
            $table->string('percentage_of')->nullable();
            $table->string('formula')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // ==================== Employee Salary Structure ====================
        if (!Schema::hasTable('employee_salary_components')) Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('percentage', 8, 4)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'is_active']);
        });

        // ==================== Payroll Runs ====================
        if (!Schema::hasTable('payroll_runs')) Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('name');
            $table->integer('year');
            $table->integer('month');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date')->nullable();
            $table->enum('status', ['draft', 'processing', 'calculated', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->integer('employee_count')->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'year', 'month', 'branch_id']);
            $table->index(['organization_id', 'status']);
        });

        // ==================== Payslips ====================
        if (!Schema::hasTable('payslips')) Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('payslip_number')->unique();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->integer('working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('overtime_hours')->default(0);
            $table->integer('late_days')->default(0);
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
            $table->string('payment_method')->nullable();
            $table->string('bank_account')->nullable();
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['employee_id', 'status']);
        });

        // ==================== Employee Loans ====================
        if (!Schema::hasTable('employee_loans')) Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('loan_number')->unique();
            $table->enum('type', ['salary_advance', 'personal_loan', 'emergency', 'other'])->default('salary_advance');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);
            $table->integer('installments');
            $table->decimal('installment_amount', 15, 2);
            $table->integer('paid_installments')->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'active', 'completed', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'employee_id']);
            $table->index(['organization_id', 'status']);
        });

        // ==================== Loan Payments ====================
        if (!Schema::hasTable('loan_payments')) {
            Schema::create('loan_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('employee_loans')->cascadeOnDelete();
                $table->foreignId('payslip_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('installment_number');
                $table->decimal('amount', 15, 2);
                $table->date('due_date');
                $table->date('paid_date')->nullable();
                $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
                $table->timestamps();

                $table->index(['loan_id', 'status']);
            });
        } else {
            if (!Schema::hasColumn('loan_payments', 'payslip_id')) {
                Schema::table('loan_payments', function (Blueprint $table) {
                    $table->foreignId('payslip_id')->nullable()->after('loan_id')->constrained()->nullOnDelete();
                });
            }
        }

        // ==================== Payslip Items ====================
        if (!Schema::hasTable('payslip_items')) Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['earning', 'deduction'])->default('earning');
            $table->string('category')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['payslip_id', 'type']);
        });

        // ==================== Leave Balances ====================
        if (!Schema::hasTable('leave_balances')) Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('entitled', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('pending', 8, 2)->default(0);
            $table->decimal('carried_forward', 8, 2)->default(0);
            $table->decimal('available', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('attendances');
    }
};
