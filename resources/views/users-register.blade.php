@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Register User</h1>
                <p>Create a new system account. Only administrators can register users.</p>
            </div>
            <a class="outline-btn" href="{{ route('users') }}" style="text-decoration:none;">← Back to Users</a>
        </div>

        @if ($errors->any())
            <div class="alert">
                <div class="alert-icon">!</div>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="form-panel" style="max-width: 560px;">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="form-grid">
                    <label class="full">Full Name
                        <input name="name" value="{{ old('name') }}" placeholder="Juan Dela Cruz" required autofocus>
                    </label>
                    <label>Username
                        <input name="username" value="{{ old('username') }}" placeholder="jdelacruz" autocomplete="username" required>
                    </label>
                    <label>Email
                        <input name="email" type="email" value="{{ old('email') }}" placeholder="juan@example.com" required>
                    </label>
                    <label>Password
                        <input name="password" type="password" autocomplete="new-password" required>
                    </label>
                    <label>Confirm Password
                        <input name="password_confirmation" type="password" autocomplete="new-password" required>
                    </label>
                    <label>Role
                        <select name="role" required>
                            <option value="Staff" {{ old('role', 'Staff') === 'Staff' ? 'selected' : '' }}>Staff</option>
                            <option value="Administrator" {{ old('role') === 'Administrator' ? 'selected' : '' }}>Administrator</option>
                        </select>
                    </label>
                    <label>Status
                        <select name="status" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>
                </div>
                <div class="modal-actions" style="justify-content:flex-start;">
                    <button class="orange-btn" type="submit">Register User</button>
                    <a class="outline-btn" href="{{ route('users') }}" style="text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
