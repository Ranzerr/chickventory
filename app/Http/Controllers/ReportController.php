<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports', [
            'title' => 'Reports',
            'productCount' => Product::where('status', 'active')->count(),
            'stockInCount' => InventoryTransaction::where('type', 'stock_in')->whereMonth('occurred_at', now()->month)->count(),
            'stockOutCount' => InventoryTransaction::where('type', 'stock_out')->whereMonth('occurred_at', now()->month)->count(),
            'supplierCount' => Supplier::where('status', 'active')->count(),
            'recentTransactions' => InventoryTransaction::with('product')->latest('occurred_at')->limit(10)->get(),
        ]);
    }
}
