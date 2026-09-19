@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, {{ $currentUserName }}!</p>
            </div><a class="outline-btn" href="{{ route('reports') }}">▣ Generate Report</a>
        </div>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon">□</div>
                <div><span>Total Products</span><strong>{{ $productCount }}</strong><small>All active products</small></div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">▦</div>
                <div><span>Total Raw Material Stock</span><strong>{{ number_format($totalStock, 0) }}</strong><small>Total items in
                        stock</small></div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">!</div>
                <div><span>Low Stock Items</span><strong>{{ $lowStockCount }}</strong><small>Below minimum level</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">♙</div>
                <div><span>Suppliers</span><strong>{{ $supplierCount }}</strong><small>Active suppliers</small></div>
            </div>
        </div>
        <div class="integration-card">
            <h2>Ordering System Integration</h2>
            <div class="simple-flow"><span>🛒 ORDERING SYSTEM<br><small>Order Completed</small></span><b>→</b><span>🔗
                    INTEGRATION<br><small>Order Data</small></span><b>→</b><span>🐔 INVENTORY SYSTEM<br><small>Automatic
                        Deduction</small></span></div>
            <div class="integration-info"><span>Integration Status: <strong
                        class="connected">Connected</strong></span><span>Orders Processed Today:
                    <strong>{{ $ordersToday }}</strong></span></div>
        </div>
        <div class="dashboard-grid">
            <div class="panel">
                <div class="panel-header">
                    <h2>Recent Inventory Transactions</h2><a href="{{ route('inventory-transactions') }}">View All</a>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>@forelse ($recentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->reference ?: $transaction->transaction_code }}</td>
                                <td>{{ $transaction->product->name }}</td>
                                <td class="{{ $transaction->type === 'stock_in' ? 'positive' : 'negative' }}">
                                    {{ str_replace('_', ' ', Str::title($transaction->type)) }}</td>
                                <td>{{ number_format($transaction->quantity, 2) }} {{ $transaction->product->unit }}</td>
                                <td>{{ $transaction->occurred_at->diffForHumans() }}</td>
                                <td><span class="badge green">{{ Str::title($transaction->status) }}</span></td>
                        </tr>@empty<tr>
                                <td colspan="6">No inventory transactions yet.</td>
                            </tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <h2>Low Stock Alert</h2><a href="{{ route('products') }}">View Inventory</a>
                </div>@forelse ($lowStockMaterials as $material)
                    <div class="alert">
                        <div class="alert-icon">!</div>
                        <div><strong>{{ $material->name }}</strong>
                            <p>Current: {{ number_format($material->current_stock, 2) }} {{ $material->unit }} | Minimum:
                                {{ number_format($material->minimum_stock, 2) }} {{ $material->unit }}</p>
                        </div>
                </div>@empty<p>All raw materials are above their minimum stock level.</p>@endforelse
            </div>
        </div>
        <div class="process-card">
            <h2>How Automatic Stock Out Works</h2>
            <div class="process-flow">
                <div>
                    <div class="process-icon">🛒</div><strong>1. Order Completed</strong><small>Customer completes an
                        order</small>
                </div>
                <div class="arrow">→</div>
                <div>
                    <div class="process-icon">🔗</div><strong>2. Data Sent</strong><small>Order data reaches
                        inventory</small>
                </div>
                <div class="arrow">→</div>
                <div>
                    <div class="process-icon">▦</div><strong>3. Stock Deducted</strong><small>Inventory updates
                        automatically</small>
                </div>
                <div class="arrow">→</div>
                <div>
                    <div class="process-icon">!</div><strong>4. Alert Triggered</strong><small>Low stock items are
                        flagged</small>
                </div>
            </div>
        </div>
    </div>
@endsection