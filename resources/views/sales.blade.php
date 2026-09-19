@extends('layouts.app')
@section('content')
<div class="content">
    <div class="page-header"><div><h1>Record Sale</h1><p>Record an external or manual sale and deduct recipe materials.</p></div></div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif
    <div class="form-panel">
        <form method="POST" action="{{ route('sales.store') }}">@csrf
            <div class="form-grid">
                <label>External order ID<input name="external_order_id" placeholder="POS-1001" required></label>
                <label>Product<select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
                <label>Quantity<input type="number" name="quantity" min="1" value="1" required></label>
                <label>Order date<input type="datetime-local" name="order_date" value="{{ now()->format('Y-m-d\\TH:i') }}" required></label>
                <label>Source system<select name="source_system"><option value="manual">Manual</option><option value="POS">POS</option></select></label>
            </div>
            <button class="orange-btn" type="submit">Record Sale</button>
        </form>
    </div>
    <div class="panel"><h2>Recent Sales</h2><div class="table-wrapper"><table><thead><tr><th>External ID</th><th>Product</th><th>Quantity</th><th>Source</th><th>Date</th>@if($isAdmin)<th>Actions</th>@endif</tr></thead><tbody>
        @forelse ($orders as $order)
            <tr><td>{{ $order->external_order_id }}</td><td>{{ $order->product->name }}</td><td>{{ $order->quantity }}</td><td>{{ $order->source_system }}</td><td>{{ $order->order_date->format('M d, Y H:i') }}</td>
                @if($isAdmin)
                    <td><form method="POST" action="{{ route('sales.destroy', $order) }}" onsubmit="return confirm('Delete this sale? Ingredient stock will be restored.');">@csrf@method('DELETE')<button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button></form></td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $isAdmin ? 6 : 5 }}">No sales recorded.</td></tr>
        @endforelse
    </tbody></table></div></div>
</div>
@endsection
