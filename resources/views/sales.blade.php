@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-header">
        <div>
            <h1>Manual / Backup Sales Entry</h1>
            <p>Record offline, catering, or manual sales overrides and automatically deduct recipe materials.</p>
        </div>
    </div>

    {{-- Informational banner for automated order context --}}
    <div class="alert alert-info" style="margin-bottom: 1rem; border-left: 4px solid #3b82f6; background-color: #eff6ff; padding: 12px 16px; border-radius: 6px;">
        <strong>Note on Automated Orders:</strong> Standard customer sales are automatically processed and logged directly from the <strong>Ordering System</strong>. Use this tool specifically for offline transactions, bulk catering, or inventory reconciliation overrides.
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    <div class="form-panel">
        <form method="POST" action="{{ route('sales.store') }}">
            @csrf
            <div class="form-grid">
                <label>
                    External / Reference ID
                    <input name="external_order_id" placeholder="MANUAL-1001" required>
                </label>

                <label>
                    Product
                    <select name="product_id" required>
                        @foreach ($products as $product)
                            <option value="{{ $product->getKey() }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Quantity
                    <input type="number" name="quantity" min="1" value="1" required>
                </label>

                <label>
                    Order Date
                    <input type="datetime-local" name="order_date" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                </label>

                <label>
                    Source System
                    <select name="source_system">
                        <option value="Manual Override">Manual Override</option>
                        <option value="Catering">Catering / Event</option>
                        <option value="POS Backup">POS Offline Backup</option>
                    </select>
                </label>
            </div>

            <button class="orange-btn" type="submit">Record Manual Sale</button>
        </form>
    </div>

    <div class="panel">
        <h2>Recent Manual Sales Logs</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Reference ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Source</th>
                        <th>Date</th>
                        @if($isAdmin)
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>{{ $order->external_order_id }}</td>
                            <td>{{ $order->product->name }}</td>
                            <td>{{ $order->quantity }}</td>
                            <td>
                                <span class="badge" style="background-color: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                    {{ $order->source_system ?: 'Manual Override' }}
                                </span>
                            </td>
                            <td>{{ $order->order_date ? $order->order_date->format('M d, Y H:i') : '-' }}</td>
                            @if($isAdmin)
                                <td>
                                    <form method="POST" action="{{ route('sales.destroy', $order) }}" onsubmit="return confirm('Delete this manual sale? Product stock will be restored.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 6 : 5 }}">No manual sales recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection