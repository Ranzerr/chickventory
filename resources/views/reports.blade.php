@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Reports</h1>
                <p>Generate inventory reports for better decisions.</p>
            </div><a class="orange-btn" href="{{ route('inventory-transactions') }}">View Transactions</a>
        </div>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon">▦</div>
                <div><span>Total Products</span><strong>{{ $productCount }}</strong><small>Active inventory items</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">↓</div>
                <div><span>Stock In This Month</span><strong>{{ $stockInCount }}</strong><small>Received
                        transactions</small></div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">↑</div>
                <div><span>Stock Out This Month</span><strong>{{ $stockOutCount }}</strong><small>Released
                        transactions</small></div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">♙</div>
                <div><span>Active Suppliers</span><strong>{{ $supplierCount }}</strong><small>Current partners</small></div>
            </div>
        </div>
        <div class="panel">
            <h2 style="color: var(--orange); margin-top: 0;">Recent Inventory Activity</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>@forelse ($recentTransactions as $transaction)
                        <tr>
                            <td>{{ $transaction->reference ?: $transaction->transaction_code }}</td>
                            <td>{{ $transaction->product->name }}</td>
                            <td>{{ Str::title(str_replace('_', ' ', $transaction->type)) }}</td>
                            <td>{{ number_format($transaction->quantity, 2) }} {{ $transaction->product->unit }}</td>
                            <td>{{ $transaction->occurred_at->format('M d, Y') }}</td>
                    </tr>@empty<tr>
                            <td colspan="5">No inventory activity yet.</td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection