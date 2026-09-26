<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Services\RecipeStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderItemController extends Controller
{
    public function index(): View
    {
        return view('sales', [
            'title' => 'Record Sale',
            'products' => Product::query()
                ->select(['product_id as id', 'name', 'unit', 'status'])
                ->whereIn('status', ['active', 'available'])
                ->orderBy('name')
                ->get(),
            'orders' => OrderItem::with('product:product_id,name,unit')
                ->latest('order_date')
                ->paginate(15),
        ]);
    }

    public function store(Request $request, RecipeStockService $recipe): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'external_order_id' => ['required', 'string', 'max:100', 'unique:order_items,external_order_id'],
            'product_id' => ['required', 'exists:products,product_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'order_date' => ['required', 'date'],
            'source_system' => ['required', 'string', 'max:50'],
        ]);

        $order = DB::transaction(function () use ($validated, $recipe): OrderItem {
            $order = OrderItem::create($validated);
            $recipe->deductStockForProduct($order->product, $order->quantity, $order);
            return $order;
        });

        return $request->expectsJson() 
            ? response()->json($order->load('product:product_id,name,unit'), 201) 
            : to_route('sales')->with('success', 'Sale recorded and recipe stock deducted.');
    }

    public function destroy(OrderItem $order, RecipeStockService $recipe): RedirectResponse
    {
        DB::transaction(function () use ($order, $recipe): void {
            $recipe->restockForOrder($order);
            $order->delete();
        });

        return to_route('sales')->with('success', 'Sale deleted and ingredient stock restored.');
    }
}