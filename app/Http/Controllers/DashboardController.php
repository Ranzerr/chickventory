<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Cache the entire dashboard payload for 60 seconds
        $dashboardData = Cache::remember('dashboard.data.v1', now()->addSeconds(60), function () {
            return [
                'productCount' => Product::whereIn('status', ['active', 'available'])->count(),
                'totalStock' => RawMaterial::where('status', 'active')->sum('current_stock'),
                'lowStockCount' => RawMaterial::whereColumn('current_stock', '<', 'minimum_stock')
                    ->where('status', 'active')
                    ->count(),
                'supplierCount' => Supplier::where('status', 'active')->count(),
                
                // Optimized date range query (uses indexes)
                'ordersToday' => InventoryTransaction::where('source', 'Ordering System')
                    ->where('occurred_at', '>=', today())
                    ->count(),

                // Keep the foreign key available for eager loading the product relation.
                'recentTransactions' => InventoryTransaction::query()
                    ->select(['id', 'transaction_code', 'reference', 'product_id', 'type', 'quantity', 'status', 'occurred_at'])
                    ->whereHas('product')
                    ->with(['product' => function ($query) {
                        $query->select(['id', 'name', 'unit']);
                    }])
                    ->latest('occurred_at')
                    ->limit(10)
                    ->get(),

                'lowStockMaterials' => RawMaterial::whereColumn('current_stock', '<', 'minimum_stock')
                    ->where('status', 'active')
                    ->limit(5)
                    ->get(),
            ];
        });

        return view('dashboard', array_merge(['title' => 'Dashboard'], $dashboardData));
    }
}