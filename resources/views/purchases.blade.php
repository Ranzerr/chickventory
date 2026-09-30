@extends('layouts.app')
@section('content')
<div class="content">
    <div class="page-header">
        <div>
            <h1>Purchases & Material Receiving</h1>
            <p>Receive materials from suppliers, record purchase prices, and update ingredient unit costs.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <div class="alert-icon">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($purchaseOrder)
        <div class="integration-card" style="margin-bottom: 16px; padding: 12px 16px; background: #fffaf5; border: 1px solid var(--orange-border); border-radius: 8px;">
            <strong>Receiving Purchase Order: {{ $purchaseOrder->po_number }}</strong>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">Quantities and costs are prefilled from the purchase order. Adjust received quantity or unit cost if actual delivery differs.</p>
        </div>
    @endif

    <div class="form-panel">
        <form method="POST" action="{{ route('purchases.store') }}">
            @csrf
            @if($purchaseOrder)
                <input type="hidden" name="po_id" value="{{ $purchaseOrder->id }}">
            @endif
            <input type="hidden" name="purchase_type" value="{{ $purchaseOrder ? 'po_based' : 'direct' }}">

            <div class="form-grid">
                <label>Supplier
                    <select name="supplier_id" required>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($purchaseOrder?->supplier_id === $supplier->id)>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>Purchase Date
                    <input type="date" name="purchase_date" value="{{ now()->toDateString() }}" required>
                </label>

                @if($purchaseOrder)
                    @foreach($purchaseOrder->items as $item)
                        <label>Material
                            <select name="material_id[]" required>
                                <option value="{{ $item->material_id }}">
                                    {{ $item->material->name }} (Current Avg: ₱{{ number_format($item->material->unit_cost ?? 0, 2) }}/{{ $item->material->unit }})
                                </option>
                            </select>
                        </label>
                        <label>Quantity Received
                            <input type="number" name="quantity_received[]" min="0.01" step="0.01" value="{{ $item->quantity_ordered }}" required>
                            <small style="color: var(--muted);">Ordered: {{ $item->quantity_ordered }} {{ $item->material->unit }}</small>
                        </label>
                        <label>Unit Cost (₱)
                            <input type="number" name="unit_cost[]" min="0" step="0.0001" value="{{ $item->unit_price }}" required>
                            <small style="color: var(--muted);">Price per {{ $item->material->unit }}</small>
                        </label>
                    @endforeach
                @else
                    <label>Material
                        <select name="material_id[]" required>
                            <option value="">Select material</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">
                                    {{ $material->name }} ({{ $material->unit }}) — Current: ₱{{ number_format($material->unit_cost ?? 0, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>Quantity Received
                        <input type="number" name="quantity_received[]" min="0.01" step="0.01" placeholder="Enter quantity" required>
                    </label>
                    <label>Unit Cost (₱)
                        <input type="number" name="unit_cost[]" min="0" step="0.0001" placeholder="Enter cost per unit" required>
                    </label>
                @endif
            </div>

            <button class="orange-btn" type="submit" style="margin-top: 16px;">Receive Purchase & Update Stock</button>
        </form>
    </div>

    <div class="panel" style="margin-top: 24px;">
        <div class="panel-header">
            <div>
                <h2>Recent Purchases & Deliveries</h2>
                <small style="color: var(--muted);">Historical material purchases and weighted average cost updates</small>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Type</th>
                        <th>Materials Received</th>
                        <th>Total Amount</th>
                        @if($isAdmin)
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->purchase_date ? $purchase->purchase_date->format('M d, Y') : '-' }}</td>
                            <td><strong>{{ $purchase->supplier->name }}</strong></td>
                            <td>
                                <span class="badge {{ $purchase->purchase_type === 'po_based' ? 'green' : 'orange' }}">
                                    {{ Str::title(str_replace('_', ' ', $purchase->purchase_type)) }}
                                </span>
                            </td>
                            <td>
                                @foreach($purchase->items as $pItem)
                                    <div>
                                        • {{ $pItem->material->name ?? 'N/A' }}: 
                                        <strong>{{ number_format($pItem->quantity, 2) }} {{ $pItem->material->unit ?? '' }}</strong> 
                                        @ ₱{{ number_format($pItem->unit_cost, 2) }}
                                    </div>
                                @endforeach
                            </td>
                            <td><strong>₱{{ number_format($purchase->items->sum('subtotal'), 2) }}</strong></td>
                            @if($isAdmin)
                                <td>
                                    <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this purchase? Material stock and average costs will be recalculated.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px; color: #d32f2f;">Delete</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 6 : 5 }}">No purchases recorded yet. Fill out the form above to record delivery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection