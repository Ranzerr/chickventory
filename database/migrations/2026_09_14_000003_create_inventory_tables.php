<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('name');
            $table->string('category')->default('Ingredients');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->string('unit', 30)->default('pcs');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('type', 30);
            $table->decimal('quantity', 12, 2);
            $table->string('source')->default('Manual Entry');
            $table->string('status')->default('completed');
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['type', 'occurred_at']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
    }
};
