@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Inventory Transactions</h1>
                <p>View all stock movements, including automatic Stock Out transactions.</p>
            </div><a class="outline-btn" href="{{ route('reports') }}">▣ Generate Report</a>
        </div>
        <div class="integration-card">
            <h2>Automatic Stock Out from Ordering System</h2>
            <p>Stock Out transactions from the Ordering System are recorded here automatically.</p>
            <div class="simple-flow"><span>🛒 Ordering System<br><small>Order Completed</small></span><b>→</b><span>🔗 Order
                    Data<br><small>Sent Automatically</small></span><b>→</b><span>🐔 Inventory<br><small>Deduct
                        Stock</small></span></div>
        </div>
        <form class="toolbar" method="GET"><input name="search" value="{{ request('search') }}"
                placeholder="Search transaction or reference..."><select name="type">
                <option value="">All Transaction Types</option>
                <option value="stock_in" @selected(request('type') === 'stock_in')>Stock In</option>
                <option value="stock_out" @selected(request('type') === 'stock_out')>Stock Out</option>
            </select><button class="outline-btn" type="submit">Filter</button></form>
        <div class="panel">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date / Time</th>
                            <th>Reference</th>
                            <th>Product</th>
                            <th>Transaction</th>
                            <th>Quantity</th>
                            <th>Source</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>@forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_code }}</td>
                            <td>{{ $transaction->occurred_at->format('M d, Y h:i A') }}</td>
                            <td>{{ $transaction->reference ?: '-' }}</td>
                            <td>{{ $transaction->item_name }}</td>
                            <td class="{{ $transaction->type === 'stock_in' ? 'positive' : 'negative' }}">
                                {{ Str::title(str_replace('_', ' ', $transaction->type)) }}</td>
                            <td>{{ number_format($transaction->quantity, 2) }} {{ $transaction->unit }}</td>
                            <td>{{ $transaction->source }}</td>
                            <td><span class="badge green">{{ Str::title($transaction->status) }}</span></td>
                    </tr>@empty<tr>
                            <td colspan="8">No transactions found.</td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection