@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Purchase Orders</h1>
                <p>Plan supplier purchases before receiving materials.</p>
            </div>
        </div>
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
        <form class="toolbar" method="GET"><select name="status"><option value="">All statuses</option>@foreach(['draft','approved','partially_fulfilled','fulfilled','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ Str::title(str_replace('_', ' ', $status)) }}</option>@endforeach</select><button class="outline-btn" type="submit">Filter</button></form>
        <div class="panel">
            <h2>Create Purchase Order</h2>
            <form method="POST" action="{{ route('purchase-orders.store') }}">@csrf<div class="form-grid">
                    <label>Supplier<select name="supplier_id" required>
                            <option value="">Select PO supplier</option>@foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                        </select></label><label>Order date<input type="date" name="order_date"
                            value="{{ now()->toDateString() }}" required></label><label>Material<select name="material_id[]"
                            required>
                            <option value="">Select material</option>@foreach($materials as $material)
                            <option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach
                        </select></label><label>Quantity ordered<input type="number" name="quantity_ordered[]" min="0.01"
                            step="0.01" required></label><label>Unit price<input type="number" name="unit_price[]" min="0"
                            step="0.01" required></label></div><button class="orange-btn" type="submit">Create PO</button>
            </form>
        </div>
        <div class="panel">
            <h2>Purchase Orders</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Items</th>
                            @if($isAdmin)
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>@forelse($purchaseOrders as $po)
                        <tr>
                            <td>{{ $po->po_number }}</td>
                            <td>{{ $po->supplier->name }}</td>
                            <td>{{ $po->order_date->format('M d, Y') }}</td>
                            <td><span class="badge {{ $po->status === 'fulfilled' ? 'green' : 'orange' }}">{{ Str::title(str_replace('_', ' ', $po->status)) }}</span></td>
                            <td>{{ $po->items->count() }} @if(in_array($po->status, ['approved', 'partially_fulfilled']))<a href="{{ route('purchases', ['po_id' => $po->id]) }}">Receive</a>@endif</td>
                            @if($isAdmin)
                                <td>
                                    <div class="page-actions">
                                        @if($po->status === 'draft')
                                            <form method="POST" action="{{ route('purchase-orders.approve', $po) }}" style="display:inline">@csrf<button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Approve</button></form>
                                            <form method="POST" action="{{ route('purchase-orders.destroy', $po) }}" onsubmit="return confirm('Delete {{ $po->po_number }}?');" style="display:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                    </tr>@empty<tr>
                            <td colspan="{{ $isAdmin ? 6 : 5 }}">No purchase orders yet.</td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection