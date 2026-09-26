<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Clear all active metrics caches across dashboard and navigation badges.
     */
    private function clearMetricsCache(): void
    {
        Cache::forget('dashboard.metrics.v4');
        Cache::forget('dashboard.data.v1');
        Cache::forget('shared.low_stock_count.v1');
    }

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();
        $activeTab = $request->input('tab', 'products');

        // 1. Paginated Products with Eager Loading (Includes current_stock for recipe calculations)
        $products = Product::with([
            'recipeMaterials:id,name,unit,current_stock',
        ])
            ->whereIn('status', ['active', 'available'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            }))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('name')
            ->paginate(15);

        // 2. Load Raw Materials only when searching or on ingredients tab
        $rawMaterials = RawMaterial::select(['id', 'material_code', 'name', 'unit', 'current_stock', 'minimum_stock', 'status'])
            ->where('status', 'active')
            ->when($search && $activeTab === 'ingredients', fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('material_code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        // 3. Lightweight Lookup Lists
        $suppliers = Supplier::select(['id', 'name'])->where('status', 'active')->orderBy('name')->get();

        // 4. Query distinct categories directly from database
        $categories = Product::whereIn('status', ['active', 'available'])
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $units = collect(['pcs', 'kg', 'g', 'L', 'mL', 'pack', 'box', 'can', 'bottle', 'serving']);

        return view('products', [
            'title' => 'Products / Inventory',
            'products' => $products,
            'suppliers' => $suppliers,
            'rawMaterials' => $rawMaterials,
            'categories' => $categories,
            'units' => $units,
            'activeTab' => $activeTab,
            'isAdmin' => auth()->user()?->role === 'Admin' || session('is_admin', false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', 'unique:products,product_code'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'category' => ['required', 'string', 'max:100'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
        ]);

        $image = $request->file('image');

        Product::create([
            'product_code' => $validated['product_code'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'image' => $image?->getContent(),
            'image_mime_type' => $image?->getMimeType(),
            'category' => $validated['category'],
            'current_stock' => $validated['current_stock'] ?? 0,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'unit' => $validated['unit'],
            'status' => 'available',
        ]);

        $this->clearMetricsCache();

        return redirect()->route('products', ['tab' => 'products'])->with('success', 'Product added successfully.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', 'unique:products,product_code,'.$product->getKey().',product_id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'category' => ['required', 'string', 'max:100'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
        ]);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $validated['image'] = $image->getContent();
            $validated['image_mime_type'] = $image->getMimeType();
        }

        $product->update($validated);

        $this->clearMetricsCache();

        return redirect()->route('products', ['tab' => 'products'])->with('success', 'Product updated successfully.');
    }

    public function image(Product $product): Response
    {
        abort_unless($product->image && $product->image_mime_type, 404);

        return response($product->image)
            ->header('Content-Type', $product->image_mime_type)
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['status' => 'unavailable']);

        $this->clearMetricsCache();

        return redirect()->route('products', ['tab' => 'products'])->with('success', 'Product deleted successfully.');
    }
}