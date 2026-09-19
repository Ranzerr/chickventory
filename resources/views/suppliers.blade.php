@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Suppliers</h1>
                <p>Manage supplier information and relationships.</p>
            </div><button class="orange-btn" type="button" data-modal-open="supplier-modal">+ Add Supplier</button>
        </div>
        @if (session('success'))
            <div class="alert alert-success">
                <div class="alert-icon">✓</div>
                <div>{{ session('success') }}</div>
        </div>@endif
        @if ($errors->any())
            <div class="alert">
                <div class="alert-icon">!</div>
                <div>{{ $errors->first() }}</div>
        </div>@endif
        <form class="toolbar" method="GET"><input name="search" value="{{ request('search') }}"
                placeholder="Search suppliers..."><button class="outline-btn" type="submit">Filter</button></form>
        <div class="panel">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Supplier ID</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Products Supplied</th>
                            <th>Status</th>
                            @if($isAdmin)
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>@forelse ($suppliers as $supplier)
                        <tr>
                            <td>SUP-{{ str_pad($supplier->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->contact_person ?: '-' }}</td>
                            <td>{{ $supplier->phone ?: '-' }}</td>
                            <td>{{ $supplier->email ?: '-' }}</td>
                            <td>{{ $supplier->products_count }}</td>
                            <td><span class="badge green">{{ Str::title($supplier->status) }}</span></td>
                            @if($isAdmin)
                                <td>
                                    <div class="page-actions">
                                        <button class="outline-btn" type="button" data-modal-open="edit-supplier-modal-{{ $supplier->id }}" style="font-size: 11px; padding: 4px 10px;">Edit</button>
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete {{ $supplier->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="outline-btn" type="submit" style="font-size: 11px; padding: 4px 10px;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                    </tr>@empty<tr>
                            <td colspan="{{ $isAdmin ? 8 : 7 }}">No suppliers found.</td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    <div class="modal" id="supplier-modal" data-modal hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="supplier-modal-title">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Partners</p>
                    <h2 id="supplier-modal-title">Add supplier</h2>
                </div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
            </div>
            <form method="POST" action="{{ route('suppliers.store') }}">@csrf<div class="form-grid"><label>Supplier
                        name<input name="name" value="{{ old('name') }}" placeholder="Fresh Farms"
                            required></label><label>Contact person<input name="contact_person"
                            value="{{ old('contact_person') }}" placeholder="Maria Santos"></label><label>Phone<input
                            name="phone" value="{{ old('phone') }}" placeholder="0917 555 0123"></label><label>Email<input
                            name="email" type="email" value="{{ old('email') }}" placeholder="supplier@example.com"></label>
                </div>
                <div class="modal-actions"><button class="outline-btn" type="button" data-modal-close>Cancel</button><button
                        class="orange-btn" type="submit">Save Supplier</button></div>
            </form>
        </section>
    </div>
    @if($isAdmin)
        @foreach ($suppliers as $supplier)
            <div class="modal" id="edit-supplier-modal-{{ $supplier->id }}" data-modal hidden>
                <div class="modal-backdrop" data-modal-close></div>
                <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="edit-supplier-modal-title-{{ $supplier->id }}">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow">Partners</p>
                            <h2 id="edit-supplier-modal-title-{{ $supplier->id }}">Edit {{ $supplier->name }}</h2>
                        </div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
                    </div>
                    <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-grid">
                            <label>Supplier name<input name="name" value="{{ $supplier->name }}" required></label>
                            <label>Contact person<input name="contact_person" value="{{ $supplier->contact_person }}"></label>
                            <label>Phone<input name="phone" value="{{ $supplier->phone }}"></label>
                            <label>Email<input name="email" type="email" value="{{ $supplier->email }}"></label>
                            <label class="login-remember"><input type="checkbox" name="requires_po" value="1" {{ $supplier->requires_po ? 'checked' : '' }}> Requires purchase order</label>
                        </div>
                        <div class="modal-actions"><button class="outline-btn" type="button" data-modal-close>Cancel</button><button class="orange-btn" type="submit">Save Changes</button></div>
                    </form>
                </section>
            </div>
        @endforeach
    @endif
@endpush