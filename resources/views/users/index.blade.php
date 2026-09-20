@extends('layouts.app')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/80 dark:bg-slate-900/60 p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-white/10 shadow-sm dark:shadow-xl relative overflow-hidden backdrop-blur-sm transition-colors">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/20 dark:via-white/20 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 border border-white/20 shrink-0">
                    <i class="fa-solid fa-users text-base sm:text-lg"></i>
                </div>
                <span>Manajemen User System</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Kelola akun Admin, Operator, dan Teknisi penanggung jawab 60 billing.
            </p>
        </div>
        <div>
            <button onclick="openCreateModal()" class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg shadow-blue-600/25 transition-all border border-white/15 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <i class="fa-solid fa-user-plus"></i> Tambah User Baru
            </button>
        </div>
    </div>

    <!-- Summary Stats Grid (2 columns on mobile, 4 columns on desktop) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white/80 dark:bg-slate-900/50 p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 relative overflow-hidden group hover:border-slate-300 dark:hover:border-white/20 shadow-sm dark:shadow-md transition-all">
            <div class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Total User</div>
            <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalUsers }}</div>
        </div>
        <div class="bg-white/80 dark:bg-slate-900/50 p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 relative overflow-hidden group hover:border-slate-300 dark:hover:border-white/20 shadow-sm dark:shadow-md transition-all">
            <div class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Admin</div>
            <div class="text-xl sm:text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $adminCount }}</div>
        </div>
        <div class="bg-white/80 dark:bg-slate-900/50 p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 relative overflow-hidden group hover:border-slate-300 dark:hover:border-white/20 shadow-sm dark:shadow-md transition-all">
            <div class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Operator</div>
            <div class="text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $operatorCount }}</div>
        </div>
        <div class="bg-white/80 dark:bg-slate-900/50 p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-white/10 relative overflow-hidden group hover:border-slate-300 dark:hover:border-white/20 shadow-sm dark:shadow-md transition-all">
            <div class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Teknisi</div>
            <div class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $technicianCount }}</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white/80 dark:bg-slate-900/50 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-white/10 shadow-sm dark:shadow-lg relative overflow-hidden transition-colors">
        <form action="{{ route('users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Cari User</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, email, No WA..." class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3.5 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Role / Peran</label>
                <select name="role" class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 transition">
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                    <option value="technician" {{ request('role') == 'technician' ? 'selected' : '' }}>Teknisi</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status Akun</label>
                <select name="status" class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 transition">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 min-h-[44px] bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-lg shadow-blue-600/25 border border-white/10 transition flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <i class="fa-solid fa-filter text-xs"></i> Filter
                </button>
                <a href="{{ route('users.index') }}" class="min-h-[44px] bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 font-medium text-xs py-2.5 px-4 rounded-xl border border-slate-300 dark:border-white/10 transition flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-slate-400">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Users Container: Dual Representation (Table for Desktop >= md, Cards for Mobile < md) -->
    <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl transition-colors">
        
        <!-- Desktop Table View (>= md) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs text-slate-700 dark:text-slate-300">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-950/60 border-b border-slate-200/80 dark:border-white/10 text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px] font-semibold">
                        <th class="py-4 px-6">User</th>
                        <th class="py-4 px-6">No WA / HP</th>
                        <th class="py-4 px-6">Role</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-white/5">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 flex items-center justify-center font-bold text-xs border border-slate-300 dark:border-white/10 shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-slate-100 text-xs">{{ $user->name }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-slate-700 dark:text-slate-300 font-mono text-xs">
                            {{ $user->phone ?? '-' }}
                        </td>
                        <td class="py-4 px-6">
                            @if($user->role === 'admin')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30 uppercase tracking-wider">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                </span>
                            @elseif($user->role === 'operator')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-300 dark:border-blue-500/30 uppercase tracking-wider">
                                    <i class="fa-solid fa-user-gear mr-1"></i> Operator
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30 uppercase tracking-wider">
                                    <i class="fa-solid fa-screwdriver-wrench mr-1"></i> Teknisi
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            @if($user->is_active)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30 uppercase tracking-wider">
                                    Aktif
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:border-rose-500/30 uppercase tracking-wider">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" 
                                    onclick="openEditModal(this)" 
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-phone="{{ $user->phone ?? '' }}"
                                    data-role="{{ $user->role }}"
                                    data-active="{{ $user->is_active ? '1' : '0' }}"
                                    class="min-h-[36px] min-w-[36px] p-2 text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-blue-300 dark:hover:bg-slate-800/60 rounded-xl transition border border-transparent hover:border-slate-200 dark:hover:border-white/10 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-400" 
                                    title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="min-h-[36px] min-w-[36px] p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-slate-800/60 rounded-xl transition border border-transparent hover:border-rose-500/20 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-rose-400" title="Hapus User">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">
                            Tidak ada data user ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card List (< md) -->
        <div class="block md:hidden divide-y divide-slate-200 dark:divide-white/5">
            @forelse($users as $user)
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 flex items-center justify-center font-bold text-sm border border-slate-300 dark:border-white/10 shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-slate-100 text-sm">{{ $user->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 break-all">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div>
                        @if($user->is_active)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30 uppercase tracking-wider">
                                Aktif
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:border-rose-500/30 uppercase tracking-wider">
                                Nonaktif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs">
                    <div class="flex items-center gap-2">
                        @if($user->role === 'admin')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30 uppercase tracking-wider">
                                <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                            </span>
                        @elseif($user->role === 'operator')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-300 dark:border-blue-500/30 uppercase tracking-wider">
                                <i class="fa-solid fa-user-gear mr-1"></i> Operator
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30 uppercase tracking-wider">
                                <i class="fa-solid fa-screwdriver-wrench mr-1"></i> Teknisi
                            </span>
                        @endif

                        @if($user->phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $user->phone) }}" target="_blank" class="min-h-[44px] px-2.5 py-1 rounded-xl text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800/80 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-400 font-mono text-xs flex items-center gap-1.5 border border-slate-200 dark:border-white/10 transition">
                            <i class="fa-brands fa-whatsapp text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            <span>{{ $user->phone }}</span>
                        </a>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" 
                            onclick="openEditModal(this)" 
                            data-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-email="{{ $user->email }}"
                            data-phone="{{ $user->phone ?? '' }}"
                            data-role="{{ $user->role }}"
                            data-active="{{ $user->is_active ? '1' : '0' }}"
                            class="min-h-[44px] min-w-[44px] px-3.5 py-2 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 rounded-xl transition border border-blue-200 dark:border-blue-800/50 flex items-center justify-center gap-1.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-400" 
                            title="Edit User">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>Edit</span>
                        </button>

                        @if($user->id !== auth()->id())
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="min-h-[44px] min-w-[44px] px-3 py-2 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 rounded-xl transition border border-rose-200 dark:border-rose-800/50 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-rose-400" title="Hapus User">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-500 dark:text-slate-400 text-xs">
                Tidak ada data user ditemukan.
            </div>
            @endforelse
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-slate-200/80 dark:border-white/10 bg-slate-50/50 dark:bg-slate-950/60">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Create User (R-10 Primary Glass Accent: Frosted Modal, Mobile Bottom Sheet) -->
<div id="createModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-950/60 backdrop-blur-md hidden p-0 sm:p-4">
    <div class="bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-white/15 rounded-t-3xl sm:rounded-3xl w-full max-w-md max-h-[92vh] sm:max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4 backdrop-blur-xl animate-modal transition-colors">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-3 sticky top-0 bg-white/95 dark:bg-slate-900/90 z-10">
            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-blue-600 dark:text-blue-400"></i> Tambah User Baru
            </h3>
            <button onclick="closeCreateModal()" class="w-11 h-11 sm:w-9 sm:h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800/70 dark:hover:bg-slate-700 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all border border-slate-200 dark:border-white/10 focus:outline-none focus:ring-2 focus:ring-slate-400" aria-label="Tutup modal"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" required class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" required class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">No WhatsApp / HP</label>
                <input type="text" name="phone" placeholder="08123456789" class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Role / Peran</label>
                <select name="role" required class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
                    <option value="technician">Teknisi (Petugas Lapangan)</option>
                    <option value="operator">Operator (Helpdesk)</option>
                    <option value="admin">Admin System</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <input type="password" name="password" required minlength="6" class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-white/10">
                <button type="button" onclick="closeCreateModal()" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium border border-slate-300 dark:border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400">Batal</button>
                <button type="submit" class="min-h-[44px] px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-lg shadow-blue-600/25 border border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User (R-10 Primary Glass Accent: Frosted Modal, Mobile Bottom Sheet) -->
<div id="editModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-950/60 backdrop-blur-md hidden p-0 sm:p-4">
    <div class="bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-white/15 rounded-t-3xl sm:rounded-3xl w-full max-w-md max-h-[92vh] sm:max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4 backdrop-blur-xl animate-modal transition-colors">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-3 sticky top-0 bg-white/95 dark:bg-slate-900/90 z-10">
            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-blue-600 dark:text-blue-400"></i> Edit Data User
            </h3>
            <button onclick="closeEditModal()" class="w-11 h-11 sm:w-9 sm:h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800/70 dark:hover:bg-slate-700 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all border border-slate-200 dark:border-white/10 focus:outline-none focus:ring-2 focus:ring-slate-400" aria-label="Tutup modal"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" id="edit_name" required class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" id="edit_email" required class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">No WhatsApp / HP</label>
                <input type="text" name="phone" id="edit_phone" class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Role / Peran</label>
                    <select name="role" id="edit_role" required class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
                        <option value="technician">Teknisi</option>
                        <option value="operator">Operator</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status Akun</label>
                    <select name="is_active" id="edit_is_active" required class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Ganti Password (Opsional)</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="6" class="w-full min-h-[44px] bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-white/10">
                <button type="button" onclick="closeEditModal()" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium border border-slate-300 dark:border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400">Batal</button>
                <button type="submit" class="min-h-[44px] px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-lg shadow-blue-600/25 border border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Close modals with Escape key (antislop R-32 keyboard accessibility)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeEditModal();
        }
    });

    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }
    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
    }

    function openEditModal(button) {
        const id = button.dataset.id;
        const name = button.dataset.name;
        const email = button.dataset.email;
        const phone = button.dataset.phone;
        const role = button.dataset.role;
        const isActive = button.dataset.active;

        document.getElementById('editForm').action = "/users/" + id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone || '';
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_is_active').value = isActive;

        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection
