<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasFk = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'loan_payments'
             AND COLUMN_NAME = 'payslip_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
        );

        if (empty($hasFk)) {
            Schema::table('loan_payments', function (Blueprint $table) {
                $table->foreignId('payslip_id')->nullable()->change();
                $table->foreign('payslip_id')->references('id')->on('payslips')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropForeign(['payslip_id']);
        });
    }
};
