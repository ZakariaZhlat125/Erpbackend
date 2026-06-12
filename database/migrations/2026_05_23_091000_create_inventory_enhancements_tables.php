<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== Add tracking type to products ====================
        if (!Schema::hasColumn('products', 'tracking_type')) Schema::table('products', function (Blueprint $table) {
            $table->enum('tracking_type', ['none', 'serial', 'batch'])->default('none')->after('type');
            $table->boolean('has_expiry')->default(false)->after('tracking_type');
        });

        // ==================== Serial Numbers ====================
        if (!Schema::hasTable('serial_numbers')) Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'damaged', 'expired'])->default('available');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'product_id']);
            $table->index(['organization_id', 'status']);
            $table->index('warehouse_id');
        });

        // ==================== Batches ====================
        if (!Schema::hasTable('batches')) Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('cost_per_unit', 15, 4)->nullable();
            $table->enum('status', ['active', 'expired', 'depleted', 'recalled'])->default('active');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'product_id', 'batch_number']);
            $table->index(['organization_id', 'product_id', 'status']);
            $table->index(['organization_id', 'expiry_date']);
            $table->index('warehouse_id');
        });

        // ==================== Stock Movements ====================
        if (!Schema::hasTable('stock_movements')) Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['purchase', 'sale', 'transfer_in', 'transfer_out', 'adjustment', 'return', 'damage', 'opening'])->default('adjustment');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 4)->nullable();
            $table->decimal('balance_before', 15, 4)->default(0);
            $table->decimal('balance_after', 15, 4)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'warehouse_id', 'product_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['organization_id', 'type']);
            $table->index('created_at');
        });

        // ==================== Stock Transfers ====================
        if (!Schema::hasTable('stock_transfers')) Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->enum('status', ['draft', 'pending', 'approved', 'shipped', 'in_transit', 'received', 'cancelled'])->default('draft');
            $table->date('request_date');
            $table->date('expected_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('shipping_notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('meta_json')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'from_warehouse_id']);
            $table->index(['organization_id', 'to_warehouse_id']);
        });

        if (!Schema::hasTable('stock_transfer_lines')) Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_requested', 15, 4);
            $table->decimal('quantity_shipped', 15, 4)->nullable();
            $table->decimal('quantity_received', 15, 4)->nullable();
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['transfer_id', 'product_id']);
        });

        // ==================== Stock Transfer Serial Numbers ====================
        if (!Schema::hasTable('stock_transfer_serials')) Schema::create('stock_transfer_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_line_id')->constrained('stock_transfer_lines')->cascadeOnDelete();
            $table->foreignId('serial_number_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_shipped')->default(false);
            $table->boolean('is_received')->default(false);
            $table->timestamps();
        });

        // ==================== Stock Balances (Denormalized for performance) ====================
        if (!Schema::hasTable('stock_balances')) Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->decimal('available_quantity', 15, 4)->default(0);
            $table->decimal('average_cost', 15, 4)->nullable();
            $table->decimal('total_value', 15, 4)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
            $table->index(['organization_id', 'warehouse_id']);
            $table->index(['organization_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('stock_transfer_serials');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('serial_numbers');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['tracking_type', 'has_expiry']);
        });
    }
};
