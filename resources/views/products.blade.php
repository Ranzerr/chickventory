@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Products / Inventory</h1>
                <p>Manage finished menu products, recipes, and kitchen ingredients.</p>
            </div>
            <div class="page-actions">
                <a class="outline-btn" href="{{ route('stock-in') }}">↓ Stock In</a>
                <button class="outline-btn" type="button" data-modal-open="product-modal">+ Add Product</button>
                <button class="orange-btn" type="button" data-modal-open="ingredient-modal">+ Add Ingredient</button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <div class="alert-icon">✓</div>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="alert">
                <div class="alert-icon">!</div>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="table-selector">
            <label for="inventory-table-select">Display table</label>
            <select id="inventory-table-select" aria-controls="products-tab ingredients-tab">
                <option value="products-tab" @selected($activeTab !== 'ingredients')>
                    Finished Products ({{ $products->count() }})
                </option>
                <option value="ingredients-tab" @selected($activeTab === 'ingredients')>
                    Raw Materials ({{ $rawMaterials->count() }})
                </option>
            </select>
        </div>

        {{-- Tab 1: Finished Products --}}
        <div id="products-tab" class="tab-content" style="{{ $activeTab === 'ingredients' ? 'display: none;' : '' }}">
            <form class="toolbar" method="GET" action="{{ route('products') }}">
                <input type="hidden" name="tab" value="products">
                <input name="search" value="{{ request('tab') !== 'ingredients' ? request('search') : '' }}" placeholder="Search products by code or name...">
                <select name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <button class="outline-btn" type="submit">Filter</button>
                @if(request('search') || request('category'))
                    <a class="outline-btn" href="{{ route('products', ['tab' => 'products']) }}" style="text-decoration:none;display:inline-flex;align-items:center;">Clear</a>
                @endif
            </form>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Finished Products (Menu Items)</h2>
                        <small style="color: var(--muted);">Items sold to customers. Each product has a recipe of ingredients that deduct automatically upon sale.</small>
                    </div>
                    <button class="orange-btn" type="button" data-modal-open="product-modal" style="font-size: 12px; padding: 7px 14px;">+ New Product</button>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Available Stock</th>
                                <th>Status</th>
                                <th>Recipe & Ingredients</th>
                                @if($isAdmin)
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td><strong>{{ $product->product_code }}</strong></td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->category }}</td>
                                    <td>{{ $product->unit }}</td>
                                    <td>
                                        <strong>{{ number_format($product->availableStock()) }}</strong>
                                        <small style="display: block; color: var(--muted);">based on recipe ingredients</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->isLowStock() ? 'orange' : 'green' }}">
                                            {{ $product->isLowStock() ? 'Low Stock' : 'In Stock' }}
                                        </span>
                                    </td>
                                    <td>
                                        <details style="min-width: 260px;">
                                            <summary style="cursor: pointer; font-weight: bold; color: var(--orange);">
                                                {{ $product->recipeMaterials->count() }} {{ Str::plural('ingredient', $product->recipeMaterials->count()) }}
                                            </summary>
                                            <div style="padding: 8px; background: #fffaf5; border: 1px solid var(--orange-border); border-radius: 6px; margin-top: 6px;">
                                                @forelse($product->recipeMaterials as $material)
                                                    <div class="recipe-summary-item" style="display: flex; justify-content: space-between; align-items: center;">
                                                        <span>• {{ $material->name }}: <strong>{{ $material->pivot->quantity_required }} {{ $material->unit }}</strong></span>
                                                        @if($isAdmin)
                                                            <form method="POST" action="{{ route('recipes.destroy', [$product, $material]) }}" onsubmit="return confirm('Remove {{ $material->name }} from this recipe?');" style="display:inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" title="Remove ingredient" style="border:none;background:transparent;color:#d32f2f;cursor:pointer;font-weight:bold;padding:0 4px;">✕</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <div style="font-size: 11px; color: var(--muted); margin-bottom: 6px;">No recipe ingredients added yet.</div>
                                                @endforelse

                                                <form method="POST" action="{{ route('recipes.store', $product) }}" class="recipe-add-row">
                                                    @csrf
                                                    <select name="material_id" required style="flex: 2;">
                                                        <option value="">Select ingredient</option>
                                                        @foreach($rawMaterials as $mat)
                                                            <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->unit }})</option>
                                                        @endforeach
                                                    </select>
                                                    <input name="quantity_required" type="number" min="0.0001" step="0.0001" placeholder="Qty / unit" required style="flex: 1; min-width: 70px;">
                                                    <button class="outline-btn" type="submit" style="padding: 5px 10px; font-size: 11px;">Add</button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                    @if($isAdmin)
                                        <td>
                                            <div class="page-actions">
                                                <button class="outline-btn" type="button" data-modal-open="edit-product-modal-{{ $product->id }}" style="font-size: 11px; padding: 4px 10px;">Edit</button>
                                                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete {{ $product->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdmin ? 8 : 7 }}">No products found. Click "+ Add Product" to create your first menu item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab 2: Ingredients & Raw Materials --}}
        <div id="ingredients-tab" class="tab-content" style="{{ $activeTab !== 'ingredients' ? 'display: none;' : '' }}">
            <form class="toolbar" method="GET" action="{{ route('products') }}">
                <input type="hidden" name="tab" value="ingredients">
                <input name="search" value="{{ request('tab') === 'ingredients' ? request('search') : '' }}" placeholder="Search ingredients by code or name...">
                <button class="outline-btn" type="submit">Filter</button>
                @if(request('search'))
                    <a class="outline-btn" href="{{ route('products', ['tab' => 'ingredients']) }}" style="text-decoration:none;display:inline-flex;align-items:center;">Clear</a>
                @endif
            </form>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Ingredients & Raw Materials</h2>
                        <small style="color: var(--muted);">Kitchen ingredients and packaging materials used in product recipes. Stock is replenished via Stock In or Purchases.</small>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a class="outline-btn" href="{{ route('stock-in') }}" style="font-size: 12px; padding: 7px 14px; text-decoration: none;">↓ Stock In</a>
                        <button class="orange-btn" type="button" data-modal-open="ingredient-modal" style="font-size: 12px; padding: 7px 14px;">+ New Ingredient</button>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Material Code</th>
                                <th>Ingredient Name</th>
                                <th>Current Stock</th>
                                <th>Minimum Stock</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rawMaterials as $material)
                                <tr>
                                    <td><strong>{{ $material->material_code }}</strong></td>
                                    <td>{{ $material->name }}</td>
                                    <td><strong>{{ number_format($material->current_stock, 2) }}</strong></td>
                                    <td>{{ number_format($material->minimum_stock, 2) }}</td>
                                    <td>{{ $material->unit }}</td>
                                    <td>
                                        <span class="badge {{ $material->isLowStock() ? 'orange' : 'green' }}">
                                            {{ $material->isLowStock() ? 'Low Stock' : 'In Stock' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="page-actions">
                                            <a class="outline-btn" href="{{ route('stock-in', ['material_id' => $material->id]) }}" style="font-size: 11px; padding: 4px 10px; text-decoration: none;">
                                                + Add Stock
                                            </a>
                                            @if($isAdmin)
                                                <button class="outline-btn" type="button" data-modal-open="edit-ingredient-modal-{{ $material->id }}" style="font-size: 11px; padding: 4px 10px;">Edit</button>
                                                <form method="POST" action="{{ route('ingredients.destroy', $material) }}" onsubmit="return confirm('Delete {{ $material->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No ingredients found. Click "+ Add Ingredient" to register kitchen raw materials.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- Modal 1: Add Finished Product --}}
    <div class="modal" id="product-modal" data-modal hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="product-modal-title">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Menu & Inventory</p>
                    <h2 id="product-modal-title">Add Finished Product</h2>
                    <small style="color: var(--muted);">Create a menu item sold to customers. You can attach recipe ingredients after creating it.</small>
                </div>
                <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
            </div>
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                <div class="form-grid">
                    <label>Product Code
                        <input name="product_code" placeholder="e.g. PRD-001" value="{{ old('product_code') }}" required>
                    </label>
                    <label>Product Name
                        <input name="name" placeholder="e.g. 1-pc Fried Chicken with Rice" value="{{ old('name') }}" required>
                    </label>
                    <label>Category
                        <input name="category" placeholder="e.g. Meals, Combos, Beverages" list="category-suggestions" value="{{ old('category') }}" required>
                        <datalist id="category-suggestions">
                            @foreach($categories as $category)
                                <option value="{{ $category }}"></option>
                            @endforeach
                            <option value="Meals"></option>
                            <option value="Combos"></option>
                            <option value="Snacks"></option>
                            <option value="Beverages"></option>
                            <option value="Sides"></option>
                        </datalist>
                    </label>
                    <label>Unit
                        <select name="unit" required>
                            @foreach($units as $unit)
                                <option value="{{ $unit }}" @selected(old('unit') === $unit || $unit === 'pcs')>{{ $unit }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Current Stock (Optional)
                        <input name="current_stock" type="number" min="0" step="0.01" value="{{ old('current_stock', 0) }}">
                    </label>
                    <label>Minimum Stock (Optional)
                        <input name="minimum_stock" type="number" min="0" step="0.01" value="{{ old('minimum_stock', 0) }}">
                    </label>
                </div>
                <div class="modal-actions">
                    <button class="outline-btn" type="button" data-modal-close>Cancel</button>
                    <button class="orange-btn" type="submit">Save Product</button>
                </div>
            </form>
        </section>
    </div>

    {{-- Modal 2: Add Ingredient / Raw Material --}}
    <div class="modal" id="ingredient-modal" data-modal hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="ingredient-modal-title">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Kitchen Inventory</p>
                    <h2 id="ingredient-modal-title">Add Ingredient / Raw Material</h2>
                    <small style="color: var(--muted);">Register raw materials used in recipes (e.g. Chicken, Cooking Oil, Flour, Packaging).</small>
                </div>
                <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
            </div>
            <form method="POST" action="{{ route('ingredients.store') }}">
                @csrf
                <div class="form-grid">
                    <label>Material Code
                        <input name="material_code" placeholder="e.g. ING-001 or RM-001" value="{{ old('material_code') }}" required>
                    </label>
                    <label>Ingredient Name
                        <input name="name" placeholder="e.g. Chicken Breast, Cooking Oil" value="{{ old('name') }}" required>
                    </label>
                    <label>Unit of Measurement
                        <select name="unit" required>
                            <option value="kg" @selected(old('unit') === 'kg')>kg (Kilograms)</option>
                            <option value="g" @selected(old('unit') === 'g')>g (Grams)</option>
                            <option value="L" @selected(old('unit') === 'L')>L (Liters)</option>
                            <option value="mL" @selected(old('unit') === 'mL')>mL (Milliliters)</option>
                            <option value="pcs" @selected(old('unit') === 'pcs')>pcs (Pieces)</option>
                            <option value="pack" @selected(old('unit') === 'pack')>pack (Packs)</option>
                            <option value="box" @selected(old('unit') === 'box')>box (Boxes)</option>
                            <option value="can" @selected(old('unit') === 'can')>can (Cans)</option>
                            <option value="bottle" @selected(old('unit') === 'bottle')>bottle (Bottles)</option>
                        </select>
                    </label>
                    <label>Initial Stock
                        <input name="current_stock" type="number" min="0" step="0.01" value="{{ old('current_stock', 0) }}" required>
                    </label>
                    <label>Minimum Stock Alert
                        <input name="minimum_stock" type="number" min="0" step="0.01" value="{{ old('minimum_stock', 10) }}" required>
                    </label>
                </div>
                <div class="modal-actions">
                    <button class="outline-btn" type="button" data-modal-close>Cancel</button>
                    <button class="orange-btn" type="submit">Save Ingredient</button>
                </div>
            </form>
        </section>
    </div>

    @if($isAdmin)
        {{-- Edit modals: Finished Products --}}
        @foreach($products as $product)
            <div class="modal" id="edit-product-modal-{{ $product->id }}" data-modal hidden>
                <div class="modal-backdrop" data-modal-close></div>
                <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="edit-product-modal-title-{{ $product->id }}">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow">Menu & Inventory</p>
                            <h2 id="edit-product-modal-title-{{ $product->id }}">Edit {{ $product->name }}</h2>
                        </div>
                        <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
                    </div>
                    <form method="POST" action="{{ route('products.update', $product) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-grid">
                            <label>Product Code<input name="product_code" value="{{ $product->product_code }}" required></label>
                            <label>Product Name<input name="name" value="{{ $product->name }}" required></label>
                            <label>Category<input name="category" value="{{ $product->category }}" required></label>
                            <label>Unit
                                <select name="unit" required>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit }}" @selected($product->unit === $unit)>{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Current Stock<input name="current_stock" type="number" min="0" step="0.01" value="{{ $product->current_stock }}"></label>
                            <label>Minimum Stock<input name="minimum_stock" type="number" min="0" step="0.01" value="{{ $product->minimum_stock }}"></label>
                        </div>
                        <div class="modal-actions">
                            <button class="outline-btn" type="button" data-modal-close>Cancel</button>
                            <button class="orange-btn" type="submit">Save Changes</button>
                        </div>
                    </form>
                </section>
            </div>
        @endforeach

        {{-- Edit modals: Ingredients --}}
        @foreach($rawMaterials as $material)
            <div class="modal" id="edit-ingredient-modal-{{ $material->id }}" data-modal hidden>
                <div class="modal-backdrop" data-modal-close></div>
                <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="edit-ingredient-modal-title-{{ $material->id }}">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow">Kitchen Inventory</p>
                            <h2 id="edit-ingredient-modal-title-{{ $material->id }}">Edit {{ $material->name }}</h2>
                        </div>
                        <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
                    </div>
                    <form method="POST" action="{{ route('ingredients.update', $material) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-grid">
                            <label>Material Code<input name="material_code" value="{{ $material->material_code }}" required></label>
                            <label>Ingredient Name<input name="name" value="{{ $material->name }}" required></label>
                            <label>Unit
                                <select name="unit" required>
                                    <option value="kg" @selected($material->unit === 'kg')>kg (Kilograms)</option>
                                    <option value="g" @selected($material->unit === 'g')>g (Grams)</option>
                                    <option value="L" @selected($material->unit === 'L')>L (Liters)</option>
                                    <option value="mL" @selected($material->unit === 'mL')>mL (Milliliters)</option>
                                    <option value="pcs" @selected($material->unit === 'pcs')>pcs (Pieces)</option>
                                    <option value="pack" @selected($material->unit === 'pack')>pack (Packs)</option>
                                    <option value="box" @selected($material->unit === 'box')>box (Boxes)</option>
                                    <option value="can" @selected($material->unit === 'can')>can (Cans)</option>
                                    <option value="bottle" @selected($material->unit === 'bottle')>bottle (Bottles)</option>
                                </select>
                            </label>
                            <label>Current Stock<input name="current_stock" type="number" min="0" step="0.01" value="{{ $material->current_stock }}" required></label>
                            <label>Minimum Stock<input name="minimum_stock" type="number" min="0" step="0.01" value="{{ $material->minimum_stock }}" required></label>
                        </div>
                        <div class="modal-actions">
                            <button class="outline-btn" type="button" data-modal-close>Cancel</button>
                            <button class="orange-btn" type="submit">Save Changes</button>
                        </div>
                    </form>
                </section>
            </div>
        @endforeach
    @endif

    <script>
        document.querySelectorAll('[data-tab-target]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.tabTarget;
                document.querySelectorAll('.tab-content').forEach((content) => {
                    content.style.display = 'none';
                });

                const targetEl = document.getElementById(targetId);
                if (targetEl) {
                    targetEl.style.display = 'block';
                }

                // Update URL query parameter without page reload
                const tabParam = targetId === 'ingredients-tab' ? 'ingredients' : 'products';
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabParam);
                window.history.replaceState({}, '', url.toString());
            });
        });

        const tableSelect = document.getElementById('inventory-table-select');
        if (tableSelect) {
            tableSelect.addEventListener('change', () => {
                const targetId = tableSelect.value;

                document.querySelectorAll('.tab-content').forEach((content) => {
                    content.style.display = content.id === targetId ? 'block' : 'none';
                });

                const url = new URL(window.location.href);
                url.searchParams.set('tab', targetId === 'ingredients-tab' ? 'ingredients' : 'products');
                window.history.replaceState({}, '', url.toString());
            });
        }
    </script>
@endpush