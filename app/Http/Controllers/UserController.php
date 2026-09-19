<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('email', 'like', '%'.$request->string('search').'%')
                    ->orWhere('username', 'like', '%'.$request->string('search').'%');
            }))
            ->orderBy('name')->get();

        return view('users', [
            'title' => 'User Management',
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('users-register', ['title' => 'Register User']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['Administrator', 'Staff'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        User::create($validated);

        return to_route('users')->with('success', 'User registered successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:4'],
            'role' => ['required', 'string', Rule::in(['Administrator', 'Staff'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return to_route('users')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return to_route('users')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return to_route('users')->with('success', 'User deleted successfully.');
    }
}
