@extends('layouts.app')
@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')
<div class="animate-in">
    <div class="page-header">
        <div>
            <h1>Manajemen User</h1>
            <p>Kelola akun dan hak akses pengguna sistem</p>
        </div>
        <a href="{{ route('master.users.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Tambah User
        </a>
    </div>

    @php
    $roleColors = [
        'admin'      => ['bg' => '#ede9fe', 'color' => '#6d28d9'],
        'purchasing' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
        'gudang'     => ['bg' => '#d1fae5', 'color' => '#065f46'],
        'sales'      => ['bg' => '#fef3c7', 'color' => '#92400e'],
        'finance'    => ['bg' => '#fee2e2', 'color' => '#991b1b'],
    ];
    @endphp

    <div class="grid grid-4" style="margin-bottom:24px;">
        @foreach($roleColors as $role => $color)
        @php $count = $users->where('role', $role)->count(); @endphp
        <div class="stat-card">
            <div class="icon" style="background:{{ $color['bg'] }}; color:{{ $color['color'] }};">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="value">{{ $count }}</div>
            <div class="label">{{ ucfirst($role) }}</div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Pengguna</h3>
            <span style="font-size:13px; color:var(--text-secondary);">{{ $users->count() }} user</span>
        </div>
        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th style="text-align:center;">Role</th>
                        <th>Bergabung</th>
                        <th style="text-align:center;">Email Verified</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#6366f1,#4f46e5); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;">{{ $user->name }}</div>
                                    @if($user->id === auth()->id())
                                    <div style="font-size:11px; color:var(--primary);">Anda</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td style="text-align:center;">
                            @php $rc = $roleColors[$user->role] ?? ['bg'=>'#f1f5f9','color'=>'#64748b']; @endphp
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; background:{{ $rc['bg'] }}; color:{{ $rc['color'] }};">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td style="text-align:center;">
                            @if($user->email_verified_at)
                            <i class="fa-solid fa-circle-check" style="color:var(--success);"></i>
                            @else
                            <i class="fa-solid fa-circle-xmark" style="color:var(--danger);"></i>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="{{ route('master.users.edit', $user) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                <button type="button" data-confirm-delete="del-user-{{ $user->id }}" data-name="{{ $user->name }} ({{ $user->email }})" class="btn btn-danger btn-sm btn-icon" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <form id="del-user-{{ $user->id }}" method="POST" action="{{ route('master.users.destroy', $user) }}" style="display:none;">
                                    @csrf @method('DELETE')
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:48px; color:var(--text-secondary);">Belum ada user</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
