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
        $metrics = Cache::remember('dashboard.metrics.v4', now()->addSeconds(15), fn () => [
            'productCount' => Product::where('status', 'active')->count(),
            'totalStock' => RawMaterial::where('status', 'active')->sum('current_stock'),
            'lowStockCount' => RawMaterial::whereColumn('current_stock', '<', 'minimum_stock')->where('status', 'active')->count(),
            'supplierCount' => Supplier::where('status', 'active')->count(),
            'ordersToday' => InventoryTransaction::where('source', 'Ordering System')->whereDate('occurred_at', today())->count(),
        ]);

        return view('dashboard', ['title' => 'Dashboard'] + $metrics + [
            'recentTransactions' => InventoryTransaction::with('product')->latest('occurred_at')->limit(5)->get(),
            'lowStockMaterials' => RawMaterial::whereColumn('current_stock', '<', 'minimum_stock')->where('status', 'active')->limit(5)->get(),
        ]);
    }
}
