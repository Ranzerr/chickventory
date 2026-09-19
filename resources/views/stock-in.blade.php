@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Stock In</h1>
                <p>Record ingredients and products received into inventory.</p>
            </div>
            <a class="outline-btn" href="{{ route('products') }}">← Back to Inventory</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                <div class="alert-icon">✓</div>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="alert">
                <div class="alert-icon">!</div>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form class="form-panel" method="POST" action="{{ route('stock-in.store') }}">
            @csrf
            <div class="form-grid">
                <label>Inventory Item to Stock In
                    <select name="stock_item" required>
                        <option value="">-- Select Ingredient or Product --</option>
                        @if($rawMaterials->isNotEmpty())
                            <optgroup label="🥕 Ingredients & Raw Materials">
                                @foreach ($rawMaterials as $material)
                                    <option value="ingredient:{{ $material->id }}" @selected(old('stock_item', $selectedItem) === 'ingredient:'.$material->id)>
                                        {{ $material->name }} ({{ $material->unit }}) — Current: {{ number_format($material->current_stock, 2) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if($products->isNotEmpty())
                            <optgroup label="🍽 Finished Products">
                                @foreach ($products as $product)
                                    <option value="product:{{ $product->id }}" @selected(old('stock_item', $selectedItem) === 'product:'.$product->id)>
                                        {{ $product->name }} ({{ $product->unit }}) — Current: {{ number_format($product->current_stock, 2) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </label>
                <label>Supplier (Optional)
                    <select name="supplier_id">
                        <option value="">Unassigned</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Quantity Received
                    <input name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity') }}" placeholder="Enter quantity" required>
                </label>
                <label>Date Received
                    <input name="date_received" type="date" value="{{ old('date_received', now()->toDateString()) }}" required>
                </label>
                <label class="full">Remarks
                    <textarea name="remarks" placeholder="Enter batch number, delivery notes, or remarks...">{{ old('remarks') }}</textarea>
                </label>
            </div>
            <button class="orange-btn" type="submit">Save Stock In</button>
        </form>

        <div class="panel">
            <div class="panel-header">
                <h2>Recent Stock-In Activity</h2>
                <small style="color: var(--muted);">Latest materials and products received</small>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Reference / Note</th>
                            <th>Date</th>
                            <th>Item Name</th>
                            <th>Item Type</th>
                            <th>Quantity Received</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentStockIns as $transaction)
                            <tr>
                                <td>{{ $transaction->reference ?: '-' }}</td>
                                <td>{{ $transaction->date ? \Carbon\Carbon::parse($transaction->date)->format('M d, Y') : '-' }}</td>
                                <td><strong>{{ $transaction->item_name }}</strong></td>
                                <td>
                                    <span class="badge {{ $transaction->item_type === 'Ingredient' ? 'orange' : 'green' }}">
                                        {{ $transaction->item_type }}
                                    </span>
                                </td>
                                <td>{{ number_format($transaction->quantity, 2) }} {{ $transaction->unit }}</td>
                                <td><span class="badge green">{{ Str::title($transaction->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No stock-in transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection