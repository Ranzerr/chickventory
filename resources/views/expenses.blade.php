@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Supply Expenses</h1>
                <p>Record supply purchases, update ingredient stocks, and track material costs for sales monitoring.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <div class="alert-icon">✓</div>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="alert">
                <div class="alert-icon">!</div>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        {{-- Unified Supply Expense Form --}}
        <div class="form-panel">
            <form method="POST" action="{{ route('expenses.store') }}">
                @csrf
                <div class="form-grid">
                    <label>Supplier
                        <select name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>Expense Date
                        <input name="expense_date" type="date" value="{{ now()->toDateString() }}" required>
                    </label>

                    <label>Description / Notes
                        <input name="description" placeholder="e.g. Weekly chicken & oil supply purchase">
                    </label>
                </div>

                <div style="margin-top: 16px; padding: 16px; background: #fffaf5; border: 1px solid var(--orange-border); border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h3 style="margin: 0; font-size: 14px; color: var(--orange);">Received Ingredients & Costs</h3>
                        <button type="button" id="add-item-btn" class="orange-btn" style="padding: 4px 12px; font-size: 12px;">
                            + Add Item
                        </button>
                    </div>

                    {{-- Dynamic Items Container --}}
                    <div id="items-container">
                        {{-- Initial Item Row --}}
                        <div class="item-row form-grid" style="margin-bottom: 12px; align-items: flex-end;">
                            <label>Raw Material
                                <select name="material_id[]" required>
                                    <option value="">Select Material</option>
                                    @foreach($materials as $material)
                                        <option value="{{ $material->id }}">
                                            {{ $material->name }} ({{ $material->unit }}) — Current Avg: ₱{{ number_format($material->unit_cost ?? 0, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label>Quantity Received
                                <input name="quantity_received[]" type="number" min="0.01" step="0.01" placeholder="Enter quantity" required>
                            </label>

                            <label>Unit Cost (₱)
                                <input name="unit_cost[]" type="number" min="0" step="0.0001" placeholder="Cost per unit" required>
                            </label>

                            <div style="padding-bottom: 2px;">
                                <button type="button" class="outline-btn remove-item-btn" style="color: #d32f2f; border-color: #d32f2f; padding: 6px 12px; width: 100%; font-size: 12px;" disabled>
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="orange-btn" type="submit" style="margin-top: 16px;">Save Supply Expense & Update Stock</button>
            </form>
        </div>

        {{-- Expenses Register Table --}}
        <div class="panel" style="margin-top: 24px;">
            <div class="panel-header">
                <h2>Supply Expense Register</h2>
                <small style="color: var(--muted);">Unified register of supply expenses backed by purchase breakdowns</small>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description / Supplier</th>
                            <th>Total Amount</th>
                            <th>Transferred to Sales</th>
                            <th>Items Breakdown (Purchases Table)</th>
                            @if($isAdmin)
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $expense->expense_date ? $expense->expense_date->format('M d, Y') : '-' }}</td>
                                <td>
                                    <strong>{{ $expense->description }}</strong>
                                    @if($expense->supplier)
                                        <br><small style="color: var(--muted);">Supplier: {{ $expense->supplier->name }}</small>
                                    @endif
                                </td>
                                <td><strong style="color: #d32f2f;">₱{{ number_format($expense->amount, 2) }}</strong></td>
                                <td>
                                    <span class="badge {{ $expense->transferred_to_sales ? 'green' : 'orange' }}">
                                        {{ $expense->transferred_to_sales ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    @if($expense->purchase && $expense->purchase->items->isNotEmpty())
                                        <details>
                                            <summary style="cursor: pointer; color: var(--orange); font-weight: bold; font-size: 12px;">
                                                View {{ $expense->purchase->items->count() }} {{ Str::plural('Item', $expense->purchase->items->count()) }}
                                            </summary>
                                            <div style="padding: 6px; background: #fffaf5; border: 1px solid var(--orange-border); border-radius: 4px; margin-top: 4px; font-size: 11px;">
                                                @foreach($expense->purchase->items as $pItem)
                                                    <div>
                                                        • {{ $pItem->material->name ?? 'N/A' }}: 
                                                        <strong>{{ number_format($pItem->quantity_received, 2) }} {{ $pItem->material->unit ?? '' }}</strong> 
                                                        @ ₱{{ number_format($pItem->unit_cost, 2) }} = ₱{{ number_format($pItem->subtotal, 2) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span style="color: var(--muted); font-size: 12px;">No Items Linked</span>
                                    @endif
                                </td>
                                @if($isAdmin)
                                    <td>
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this supply expense? Stock increments will be reversed.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px; color: #d32f2f;">Delete</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 6 : 5 }}">No supply expenses recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Script for handling Dynamic Item Rows --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('items-container');
            const addBtn = document.getElementById('add-item-btn');

            addBtn.addEventListener('click', function () {
                const firstRow = container.querySelector('.item-row');
                const newRow = firstRow.cloneNode(true);

                // Reset field values in the cloned row
                newRow.querySelectorAll('select, input').forEach(input => {
                    input.value = '';
                });

                container.appendChild(newRow);
                updateRemoveButtons();
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-item-btn')) {
                    const rows = container.querySelectorAll('.item-row');
                    if (rows.length > 1) {
                        e.target.closest('.item-row').remove();
                        updateRemoveButtons();
                    }
                }
            });

            function updateRemoveButtons() {
                const rows = container.querySelectorAll('.item-row');
                rows.forEach(row => {
                    const removeBtn = row.querySelector('.remove-item-btn');
                    removeBtn.disabled = (rows.length === 1);
                });
            }
        });
    </script>
@endsection