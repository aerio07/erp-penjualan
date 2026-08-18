<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')->get();
        return view('master.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('master.users.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,purchasing,gudang,sales,finance',
            'password' => 'required|min:8|confirmed',
        ]);

        User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'role'              => $request->role,
            'password'          => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('master.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user): View
    {
        return view('master.users.form', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('master.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:admin,purchasing,gudang,sales,finance',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $data = $request->only('name', 'email', 'role');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('master.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }
        $user->delete();
        return redirect()->route('master.users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
