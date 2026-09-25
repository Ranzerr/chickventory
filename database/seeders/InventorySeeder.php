<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Core system settings required for app functionality
        foreach ([
            'system_name' => 'Chicky Fryday Inventory Management System',
            'currency' => 'PHP',
            'description' => 'Inventory management for Chicky Fryday.',
            'low_stock_threshold' => '10',
            'default_unit' => 'Pieces',
        ] as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
