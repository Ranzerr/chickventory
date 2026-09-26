<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Cache report metrics for 60 seconds to avoid repeating heavy aggregate counts
        $reportData = Cache::remember('reports.summary.v1', now()->addSeconds(60), function () use ($startOfMonth, $endOfMonth) {
            return [
                'productCount' => Product::where('status', 'active')->count(),
                
                // Optimized date range queries using indexes
                'stockInCount' => InventoryTransaction::where('type', 'stock_in')
                    ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                    ->count(),

                'stockOutCount' => InventoryTransaction::where('type', 'stock_out')
                    ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                    ->count(),

                'supplierCount' => Supplier::where('status', 'active')->count(),

                'recentTransactions' => InventoryTransaction::with('product:id,name,unit')
                    ->latest('occurred_at')
                    ->limit(10)
                    ->get(),
            ];
        });

        return view('reports', array_merge(['title' => 'Reports'], $reportData));
    }
}