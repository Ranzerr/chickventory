<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'image')) {
            DB::statement('ALTER TABLE products ADD image MEDIUMBLOB NULL AFTER name');
        } else {
            DB::statement('ALTER TABLE products MODIFY image MEDIUMBLOB NULL');
        }

        if (! Schema::hasColumn('products', 'image_mime_type')) {
            DB::statement('ALTER TABLE products ADD image_mime_type VARCHAR(100) NULL AFTER image');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function ($table): void {
            $table->dropColumn(['image', 'image_mime_type']);
        });
    }
};
