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
        // Version 5 cache key storing plain arrays instead of Eloquent model objects
        $dashboardData = Cache::remember('dashboard.data.v5', now()->addSeconds(60), function () {
            return [
                'productCount' => Product::whereIn('status', ['active', 'available'])->count(),
                'totalStock' => RawMaterial::where('status', 'active')->sum('current_stock'),
                'lowStockCount' => RawMaterial::whereColumn('current_stock', '<', 'minimum_stock')
                    ->where('status', 'active')
                    ->count(),
                'supplierCount' => Supplier::where('status', 'active')->count(),
                
                'ordersToday' => InventoryTransaction::where('source', 'Ordering System')
                    ->where('occurred_at', '>=', today())
                    ->count(),

                'recentTransactions' => InventoryTransaction::query()
                    ->select(['id', 'transaction_code', 'reference', 'product_id', 'type', 'quantity', 'status', 'occurred_at'])
                    ->whereHas('product')
                    ->with(['product' => function ($query) {
                        $query->select(['product_id', 'name', 'unit']);
                    }])
                    ->latest('occurred_at')
                    ->limit(10)
                    ->get(),

                // Store as plain array to prevent PHP unserialize incomplete object errors
                'lowStockMaterials' => RawMaterial::whereColumn('current_stock', '<', 'minimum_stock')
                    ->where('status', 'active')
                    ->select(['id', 'name', 'current_stock', 'minimum_stock', 'unit'])
                    ->limit(5)
                    ->get()
                    ->toArray(),
            ];
        });

        return view('dashboard', array_merge(['title' => 'Dashboard'], $dashboardData));
    }
}