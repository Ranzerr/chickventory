<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('suppliers', 'requires_po')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->boolean('requires_po')->default(false)->after('status');
            });
        }

        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_code')->unique();
            $table->string('name');
            $table->string('unit', 30)->default('pcs');
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('order_date');
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->index(['status', 'order_date']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('quantity_ordered', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('purchase_type')->default('direct');
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('purchase_date');
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        if (! Schema::hasTable('supplier_material')) {
            Schema::create('supplier_material', function (Blueprint $table) {
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('material_id')->constrained('raw_materials')->cascadeOnDelete();
                $table->decimal('last_unit_cost', 12, 2)->default(0);
                $table->timestamps();
                $table->primary(['supplier_id', 'material_id']);
            });
        } else {
            Schema::table('supplier_material', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->decimal('quantity_required', 12, 4);
            $table->timestamps();
            $table->unique(['product_id', 'material_id']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('external_order_id')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->dateTime('order_date');
            $table->string('source_system')->default('manual');
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('raw_materials')->cascadeOnDelete();
            $table->string('movement_type', 30);
            $table->decimal('quantity', 12, 4);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->boolean('transferred_to_sales')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('product_recipes');
        Schema::dropIfExists('supplier_material');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('raw_materials');
        Schema::table('suppliers', fn (Blueprint $table) => $table->dropColumn('requires_po'));
    }
};
