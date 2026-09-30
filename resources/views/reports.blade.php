@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Reports & Financial Insights</h1>
                <p>Monitor inventory activity, sales revenue, product costing (COGS), and gross profit margins.</p>
            </div>
            <a class="orange-btn" href="{{ route('inventory-transactions') }}">View Transactions</a>
        </div>

        {{-- Financial Analytics Grid --}}
        <div class="summary-grid" style="margin-bottom: 24px;">
            <div class="summary-card">
                <div class="summary-icon">₱</div>
                <div>
                    <span>Total COGS (This Month)</span>
                    <strong style="color: #d32f2f;">₱{{ number_format($totalCogs ?? 0, 2) }}</strong>
                    <small>Cost of raw materials sold</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">📈</div>
                <div>
                    <span>Gross Sales Revenue</span>
                    <strong style="color: #1976d2;">₱{{ number_format($totalRevenue ?? 0, 2) }}</strong>
                    <small>Total menu sales</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">💰</div>
                <div>
                    <span>Gross Profit</span>
                    <strong style="color: #2e7d32;">₱{{ number_format($grossProfit ?? 0, 2) }}</strong>
                    @php
                        $margin = ($totalRevenue ?? 0) > 0 ? (($grossProfit ?? 0) / $totalRevenue) * 100 : 0;
                    @endphp
                    <small>
                        <span class="badge {{ $margin >= 50 ? 'green' : 'orange' }}" style="font-size: 10px;">
                            {{ number_format($margin, 1) }}% Gross Margin
                        </span>
                    </small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">▦</div>
                <div>
                    <span>Total Products</span>
                    <strong>{{ $productCount }}</strong>
                    <small>Active menu items</small>
                </div>
            </div>
        </div>

        {{-- Inventory Movement Grid --}}
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon">↓</div>
                <div>
                    <span>Stock In This Month</span>
                    <strong>{{ $stockInCount }}</strong>
                    <small>Received deliveries</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">↑</div>
                <div>
                    <span>Stock Out This Month</span>
                    <strong>{{ $stockOutCount }}</strong>
                    <small>Order sales & usage</small>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">♙</div>
                <div>
                    <span>Active Suppliers</span>
                    <strong>{{ $supplierCount }}</strong>
                    <small>Current supply partners</small>
                </div>
            </div>
        </div>

        <div class="panel" style="margin-top: 24px;">
            <div class="panel-header">
                <div>
                    <h2 style="color: var(--orange); margin: 0;">Recent Inventory & Cost Activity</h2>
                    <small style="color: var(--muted);">Latest stock movements with recorded item unit costs</small>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Product / Material</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Extended COGS</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTransactions as $transaction)
                            @php
                                $unitCost = $transaction->cost_price ?? $transaction->product->cost_to_produce ?? 0;
                                $extendedCost = $unitCost * $transaction->quantity;
                            @endphp
                            <tr>
                                <td><strong>{{ $transaction->reference ?: $transaction->transaction_code }}</strong></td>
                                <td>{{ $transaction->product->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ str_contains($transaction->type, 'in') ? 'green' : 'orange' }}">
                                        {{ Str::title(str_replace('_', ' ', $transaction->type)) }}
                                    </span>
                                </td>
                                <td>{{ number_format($transaction->quantity, 2) }} {{ $transaction->product->unit ?? '' }}</td>
                                <td>₱{{ number_format($unitCost, 2) }}</td>
                                <td><strong style="color: #d32f2f;">₱{{ number_format($extendedCost, 2) }}</strong></td>
                                <td>{{ $transaction->occurred_at ? $transaction->occurred_at->format('M d, Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No inventory activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection