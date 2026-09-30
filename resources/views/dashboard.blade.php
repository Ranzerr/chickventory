@extends('layouts.app')

@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, {{ $currentUserName }}!</p>
            </div>
            <a class="outline-btn" href="{{ route('reports') }}">▣ Generate Report</a>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon">□</div>
                <div>
                    <span>Total Products</span>
                    <strong>{{ $productCount }}</strong>
                    <small>All active menu items</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">▦</div>
                <div>
                    <span>Total Raw Material Stock</span>
                    <strong>{{ number_format($totalStock, 0) }}</strong>
                    <small>Total items in stock</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">!</div>
                <div>
                    <span>Low Stock Items</span>
                    <strong>{{ $lowStockCount }}</strong>
                    <small>Below minimum level</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">💰</div>
                <div>
                    <span>Gross Sales Revenue</span>
                    <strong style="color: #2e7d32;">₱{{ number_format($totalRevenueToday ?? 0, 2) }}</strong>
                    <small>Processed today</small>
                </div>
            </div>
        </div>

        <div class="integration-card">
            <h2>Ordering System Integration</h2>
            <div class="simple-flow">
                <span>🛒 ORDERING SYSTEM<br><small>Order Completed</small></span>
                <b>→</b>
                <span>🔗 INTEGRATION<br><small>Order Data & COGS Snapshot</small></span>
                <b>→</b>
                <span>🐔 INVENTORY SYSTEM<br><small>Automatic Deduction</small></span>
            </div>
            <div class="integration-info">
                <span>Integration Status: <strong class="connected">Connected</strong></span>
                <span>Orders Processed Today: <strong>{{ $ordersToday }}</strong></span>
            </div>
        </div>

        <div class="dashboard-grid">
            {{-- Panel 1: Recent Inventory Transactions --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>Recent Inventory Transactions</h2>
                    <a href="{{ route('inventory-transactions') }}">View All</a>
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
                        <tbody>
                            @forelse ($recentTransactions as $transaction)
                                @php
                                    $tx = (object) $transaction;
                                    $reference = !empty($tx->reference) ? $tx->reference : ($tx->transaction_code ?? '-');
                                    $product = is_object($tx->product ?? null) ? $tx->product : null;
                                    $productName = $product->name ?? ($tx->product_name ?? 'N/A');
                                    $productUnit = $product->unit ?? ($tx->product_unit ?? 'pcs');
                                    
                                    $occurredAt = is_string($tx->occurred_at ?? null) 
                                        ? \Illuminate\Support\Carbon::parse($tx->occurred_at) 
                                        : ($tx->occurred_at ?? null);
                                    
                                    $txType = $tx->type ?? '';
                                    $txStatus = $tx->status ?? 'completed';
                                    $txQty = (float) ($tx->quantity ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $reference }}</td>
                                    <td>{{ $productName }}</td>
                                    <td class="{{ $txType === 'stock_in' ? 'positive' : 'negative' }}">
                                        {{ str_replace('_', ' ', Str::title($txType)) }}
                                    </td>
                                    <td>{{ number_format($txQty, 2) }} {{ $productUnit }}</td>
                                    <td>{{ $occurredAt ? $occurredAt->diffForHumans() : '-' }}</td>
                                    <td><span class="badge green">{{ Str::title($txStatus) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">No inventory transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Panel 2: Alerts (Low Stock & Low Margin Warnings) --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>Low Stock Alert</h2>
                    <a href="{{ route('products', ['tab' => 'ingredients']) }}">View Inventory</a>
                </div>
                                                @forelse ($lowStockMaterials as $material)
                                <div class="alert" style="margin-bottom: 8px;">
                                    <div class="alert-icon">!</div>
                                    <div style="flex: 1;">
                                        @if(is_array($material))
                                            <strong>{{ $material['name'] ?? 'Raw Material Alert' }}</strong>
                                            <p>
                                                Current: {{ number_format($material['current_stock'] ?? 0, 2) }} {{ $material['unit'] ?? '' }} | 
                                                Minimum: {{ number_format($material['minimum_stock'] ?? 0, 2) }} {{ $material['unit'] ?? '' }}
                                            </p>
                                        @elseif(is_object($material))
                                            <strong>{{ $material->name ?? 'Raw Material Alert' }}</strong>
                                            <p>
                                                Current: {{ number_format($material->current_stock ?? 0, 2) }} {{ $material->unit ?? '' }} | 
                                                Minimum: {{ number_format($material->minimum_stock ?? 0, 2) }} {{ $material->unit ?? '' }}
                                            </p>
                                        @else
                                            <strong>{{ $material }}</strong>
                                        @endif
                                    </div>
                                    @if(is_array($material) && !empty($material['id']))
                                        <a class="outline-btn" href="{{ route('stock-in', ['material_id' => $material['id']]) }}" style="font-size: 10px; padding: 3px 8px; text-decoration: none;">+ Stock In</a>
                                    @elseif(is_object($material) && !empty($material->id))
                                        <a class="outline-btn" href="{{ route('stock-in', ['material_id' => $material->id]) }}" style="font-size: 10px; padding: 3px 8px; text-decoration: none;">+ Stock In</a>
                                    @endif
                                </div>
                            @empty
                                <p style="font-size: 13px; color: var(--muted);">All raw materials are above their minimum stock level.</p>
                            @endforelse
                @if(isset($lowMarginProducts) && $lowMarginProducts->isNotEmpty())
                    <div class="panel-header" style="margin-top: 24px;">
                        <h2 style="color: #d32f2f;">Low Margin Alert (< 30%)</h2>
                        <a href="{{ route('products') }}">Adjust Recipe</a>
                    </div>
                    @foreach($lowMarginProducts as $prod)
                        <div class="alert" style="border-left: 4px solid #d32f2f; background: #fff5f5; margin-bottom: 8px;">
                            <div class="alert-icon" style="color: #d32f2f;">⚠️</div>
                            <div>
                                <strong>{{ $prod->name }}</strong>
                                <p>
                                    Price: ₱{{ number_format($prod->price, 2) }} | 
                                    COGS: ₱{{ number_format($prod->cost_to_produce, 2) }} | 
                                    Margin: <span style="color: #d32f2f; font-weight: bold;">{{ number_format($prod->profit_margin_percentage, 1) }}%</span>
                                </p>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="process-card">
            <h2>How Automatic Stock Out Works</h2>
            <div class="process-flow">
                <div>
                    <div class="process-icon">🛒</div>
                    <strong>1. Order Completed</strong>
                    <small>Customer completes an order</small>
                </div>
                <div class="arrow">→</div>
                <div>
                    <div class="process-icon">🔗</div>
                    <strong>2. Data Sent</strong>
                    <small>Order data & live COGS sent</small>
                </div>
                <div class="arrow">→</div>
                <div>
                    <div class="process-icon">▦</div>
                    <strong>3. Stock Deducted</strong>
                    <small>Raw materials deducted automatically</small>
                </div>
                <div class="arrow">→</div>
                <div>
                    <div class="process-icon">!</div>
                    <strong>4. Alert Triggered</strong>
                    <small>Low stock or thin margins flagged</small>
                </div>
            </div>
        </div>
    </div>
@endsection