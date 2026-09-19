@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Expenses</h1>
                <p>Track costs and mark expenses included in sales pricing.</p>
            </div>
        </div>@if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))
        <div class="alert">{{ session('error') }}</div>@endif<div class="form-panel">
            <form method="POST" action="{{ route('expenses.store') }}">@csrf<div class="form-grid"><label>Description<input
                            name="description" required></label><label>Amount<input name="amount" type="number" min="0.01"
                            step="0.01" required></label><label>Expense date<input name="expense_date" type="date"
                            value="{{ now()->toDateString() }}" required></label></div><button class="orange-btn"
                    type="submit">Save Expense</button></form>
        </div>
        <div class="panel">
            <h2>Expense Register</h2>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Transferred to Sales</th>
                        @if($isAdmin)
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>@forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->description }}</td>
                        <td>{{ number_format($expense->amount, 2) }}</td>
                        <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                        <td><span
                                class="badge {{ $expense->transferred_to_sales ? 'green' : 'orange' }}">{{ $expense->transferred_to_sales ? 'Yes' : 'No' }}</span>
                        </td>
                        @if($isAdmin)
                            <td>
                                <div class="page-actions">
                                    <form method="POST" action="{{ route('expenses.transfer', $expense) }}">@csrf<button
                                            class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Toggle</button></form>
                                    <button class="outline-btn" type="button" data-modal-open="edit-expense-modal-{{ $expense->id }}" style="font-size: 11px; padding: 4px 10px;">Edit</button>
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        @endif
                </tr>@empty<tr>
                        <td colspan="{{ $isAdmin ? 5 : 4 }}">No expenses recorded.</td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
@if($isAdmin)
    @push('modals')
        @foreach($expenses as $expense)
            <div class="modal" id="edit-expense-modal-{{ $expense->id }}" data-modal hidden>
                <div class="modal-backdrop" data-modal-close></div>
                <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="edit-expense-modal-title-{{ $expense->id }}">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow">Expenses</p>
                            <h2 id="edit-expense-modal-title-{{ $expense->id }}">Edit expense</h2>
                        </div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
                    </div>
                    <form method="POST" action="{{ route('expenses.update', $expense) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-grid">
                            <label>Description<input name="description" value="{{ $expense->description }}" required></label>
                            <label>Amount<input name="amount" type="number" min="0.01" step="0.01" value="{{ $expense->amount }}" required></label>
                            <label>Expense date<input name="expense_date" type="date" value="{{ $expense->expense_date->toDateString() }}" required></label>
                        </div>
                        <div class="modal-actions"><button class="outline-btn" type="button" data-modal-close>Cancel</button><button class="orange-btn" type="submit">Save Changes</button></div>
                    </form>
                </section>
            </div>
        @endforeach
    @endpush
@endif
