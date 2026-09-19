@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>User Management</h1>
                <p>Manage system users and access levels.</p>
            </div>
            @if ($isAdmin)
                <a class="orange-btn" href="{{ route('users.create') }}" style="text-decoration:none;">+ Register User</a>
            @endif
        </div>
        @if (session('success'))
            <div class="alert alert-success">
                <div class="alert-icon">✓</div>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="alert">
                <div class="alert-icon">!</div>
                <div>{{ session('error') }}</div>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert">
                <div class="alert-icon">!</div>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif
        <form class="toolbar" method="GET"><input name="search" value="{{ request('search') }}"
                placeholder="Search users..."><button class="outline-btn" type="submit">Filter</button></form>
        <div class="panel">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Status</th>
                            @if ($isAdmin)
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>@forelse ($users as $user)
                        <tr>
                            <td>USR-{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username ?: '-' }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role }}</td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td><span class="badge {{ $user->status === 'active' ? 'green' : 'orange' }}">{{ Str::title($user->status) }}</span></td>
                            @if ($isAdmin)
                                <td>
                                    <div class="page-actions">
                                        <button class="outline-btn" type="button" data-modal-open="edit-user-modal-{{ $user->id }}">Edit</button>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="outline-btn" type="submit" {{ $user->id === auth()->id() ? 'disabled' : '' }}>Delete</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                    </tr>@empty<tr>
                            <td colspan="{{ $isAdmin ? 8 : 7 }}">No users found.</td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@if ($isAdmin)
    @push('modals')
        @foreach ($users as $user)
            <div class="modal" id="edit-user-modal-{{ $user->id }}" data-modal hidden>
                <div class="modal-backdrop" data-modal-close></div>
                <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="edit-user-modal-title-{{ $user->id }}">
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow">Users</p>
                            <h2 id="edit-user-modal-title-{{ $user->id }}">Edit {{ $user->name }}</h2>
                        </div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
                    </div>
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-grid">
                            <label>Name<input name="name" value="{{ $user->name }}" required></label>
                            <label>Username<input name="username" value="{{ $user->username }}" required></label>
                            <label>Email<input name="email" type="email" value="{{ $user->email }}" required></label>
                            <label>New password<input name="password" type="password" placeholder="Leave blank to keep current"></label>
                            <label>Role
                                <select name="role" required>
                                    <option value="Staff" {{ $user->role === 'Staff' ? 'selected' : '' }}>Staff</option>
                                    <option value="Administrator" {{ $user->role === 'Administrator' ? 'selected' : '' }}>Administrator</option>
                                </select>
                            </label>
                            <label>Status
                                <select name="status" required>
                                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </label>
                        </div>
                        <div class="modal-actions"><button class="outline-btn" type="button" data-modal-close>Cancel</button><button class="orange-btn" type="submit">Save Changes</button></div>
                    </form>
                </section>
            </div>
        @endforeach
    @endpush
@endif
