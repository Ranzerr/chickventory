@extends('layouts.app')
@section('content')
<div class="content">
    <div class="page-header"><div><h1>Purchases</h1><p>Receive materials from suppliers and update material stock.</p></div></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($purchaseOrder)<div class="integration-card"><strong>Receiving {{ $purchaseOrder->po_number }}</strong><p>Quantities are prefilled from the purchase order. Adjust received quantity when delivery differs.</p></div>@endif
    <div class="form-panel"><form method="POST" action="{{ route('purchases.store') }}">@csrf
        @if($purchaseOrder)<input type="hidden" name="po_id" value="{{ $purchaseOrder->id }}">@endif
        <input type="hidden" name="purchase_type" value="{{ $purchaseOrder ? 'po_based' : 'direct' }}">
        <div class="form-grid">
            <label>Supplier<select name="supplier_id" required>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected($purchaseOrder?->supplier_id === $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></label>
            <label>Purchase date<input type="date" name="purchase_date" value="{{ now()->toDateString() }}" required></label>
            @if($purchaseOrder)
                @foreach($purchaseOrder->items as $item)
                    <label>Material<select name="material_id[]" required><option value="{{ $item->material_id }}">{{ $item->material->name }}</option></select></label>
                    <label>Quantity received<input type="number" name="quantity_received[]" min="0.01" step="0.01" value="{{ $item->quantity_ordered }}" required><small>Ordered: {{ $item->quantity_ordered }} {{ $item->material->unit }}</small></label>
                    <label>Unit cost<input type="number" name="unit_cost[]" min="0" step="0.01" value="{{ $item->unit_price }}" required></label>
                @endforeach
            @else
                <label>Material<select name="material_id[]" required><option value="">Select material</option>@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach</select></label>
                <label>Quantity received<input type="number" name="quantity_received[]" min="0.01" step="0.01" required></label>
                <label>Unit cost<input type="number" name="unit_cost[]" min="0" step="0.01" required></label>
            @endif
        </div>
        <button class="orange-btn" type="submit">Receive Purchase</button>
    </form></div>
    <div class="panel"><h2>Recent Purchases</h2><div class="table-wrapper"><table><thead><tr><th>Date</th><th>Supplier</th><th>Type</th><th>Materials</th><th>Total</th>@if($isAdmin)<th>Actions</th>@endif</tr></thead><tbody>@forelse($purchases as $purchase)<tr><td>{{ $purchase->purchase_date->format('M d, Y') }}</td><td>{{ $purchase->supplier->name }}</td><td>{{ Str::title($purchase->purchase_type) }}</td><td>{{ $purchase->items->pluck('material.name')->join(', ') }}</td><td>{{ number_format($purchase->items->sum('subtotal'), 2) }}</td>@if($isAdmin)<td><form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this purchase? Material stock will be reversed.');">@csrf@method('DELETE')<button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button></form></td>@endif</tr>@empty<tr><td colspan="{{ $isAdmin ? 6 : 5 }}">No purchases yet.</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
