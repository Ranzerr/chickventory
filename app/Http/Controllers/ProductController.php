<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search');

        $productsQuery = Product::with(['supplier', 'recipeMaterials'])->where('status', 'active')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('product_code', 'like', '%'.$search.'%');
            }))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->orderBy('name');

        $rawMaterialsQuery = RawMaterial::where('status', 'active')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('material_code', 'like', '%'.$search.'%');
            }))
            ->orderBy('name');

        $products = $productsQuery->get();
        $rawMaterials = $rawMaterialsQuery->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        $categories = $products->pluck('category')->filter()->unique()->sort()->values();
        $units = collect(['pcs', 'kg', 'g', 'L', 'mL', 'pack', 'box', 'can', 'bottle', 'serving'])
            ->merge($products->pluck('unit'))
            ->merge($rawMaterials->pluck('unit'))
            ->filter()->unique()->sort()->values();

        $activeTab = $request->input('tab', 'products');

        return view('products', [
            'title' => 'Products / Inventory',
            'products' => $products,
            'suppliers' => $suppliers,
            'rawMaterials' => $rawMaterials,
            'categories' => $categories,
            'units' => $units,
            'activeTab' => $activeTab,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', 'unique:products,product_code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
        ]);

        Product::create([
            'product_code' => $validated['product_code'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'current_stock' => $validated['current_stock'] ?? 0,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'unit' => $validated['unit'],
            'status' => 'active',
        ]);

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return redirect()->route('products', ['tab' => 'products'])->with('success', 'Product added successfully.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', 'unique:products,product_code,'.$product->id],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
        ]);

        $product->update($validated);

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return redirect()->route('products', ['tab' => 'products'])->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['status' => 'inactive']);

        cache()->forget('dashboard.metrics.v3');
        cache()->forget('shared.low_stock_count.v1');

        return redirect()->route('products', ['tab' => 'products'])->with('success', 'Product deleted successfully.');
    }
}
