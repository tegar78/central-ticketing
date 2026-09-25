@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden transition-colors">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 shrink-0">
                    <i class="fa-solid fa-users text-base"></i>
                </div>
                <span>Manajemen User System</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Kelola akun Admin, Operator, dan Teknisi penanggung jawab 60 billing.
            </p>
        </div>
        <div>
            <button onclick="openCreateModal()" class="w-full sm:w-auto min-h-[42px] px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm shadow-emerald-600/20 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <i class="fa-solid fa-user-plus"></i> Tambah User Baru
            </button>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">Total User</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalUsers }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-purple-600 dark:text-purple-400 font-semibold uppercase tracking-wider">Admin</div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $adminCount }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-sky-600 dark:text-sky-400 font-semibold uppercase tracking-wider">Operator</div>
            <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ $operatorCount }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 shadow-sm transition-all">
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider">Teknisi</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $technicianCount }}</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden transition-colors">
        <form action="{{ route('users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Cari User</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, email, No WA..." class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Role / Peran</label>
                <select name="role" class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 transition">
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                    <option value="technician" {{ request('role') == 'technician' ? 'selected' : '' }}>Teknisi</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status Akun</label>
                <select name="status" class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 transition">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 min-h-[42px] bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-sm shadow-emerald-600/20 transition flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <i class="fa-solid fa-filter text-xs"></i> Filter
                </button>
                <a href="{{ route('users.index') }}" class="min-h-[42px] bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 font-medium text-xs py-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-700 transition flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-slate-400">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Users Container -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm transition-colors">
        
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs text-slate-700 dark:text-slate-300">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/40 border-b border-slate-200/80 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px] font-semibold">
                        <th class="py-3.5 px-5">User</th>
                        <th class="py-3.5 px-5">No WA / HP</th>
                        <th class="py-3.5 px-5">Role</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200 flex items-center justify-center font-bold text-xs border border-slate-200 dark:border-slate-700 shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-slate-100 text-xs">{{ $user->name }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-5 text-slate-700 dark:text-slate-300 font-mono text-xs">
                            {{ $user->phone ?? '-' }}
                        </td>
                        <td class="py-4 px-5">
                            @if($user->role === 'admin')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/80 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/40 uppercase tracking-wider">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                </span>
                            @elseif($user->role === 'operator')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/40 uppercase tracking-wider">
                                    <i class="fa-solid fa-user-gear mr-1"></i> Operator
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 uppercase tracking-wider">
                                    <i class="fa-solid fa-screwdriver-wrench mr-1"></i> Teknisi
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-5">
                            @if($user->is_active)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 uppercase tracking-wider">
                                    Aktif
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40 uppercase tracking-wider">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" 
                                    onclick="openEditModal(this)" 
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-phone="{{ $user->phone ?? '' }}"
                                    data-role="{{ $user->role }}"
                                    data-active="{{ $user->is_active ? '1' : '0' }}"
                                    class="min-h-[34px] min-w-[34px] p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-slate-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-950/40 rounded-lg transition border border-transparent hover:border-emerald-200 dark:hover:border-emerald-800/40 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-emerald-400" 
                                    title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="min-h-[34px] min-w-[34px] p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-rose-950/40 rounded-lg transition border border-transparent hover:border-rose-200 dark:hover:border-rose-800/40 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-rose-400" title="Hapus User">
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

        <!-- Mobile Card List -->
        <div class="block md:hidden divide-y divide-slate-200/80 dark:divide-slate-800">
            @forelse($users as $user)
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200 flex items-center justify-center font-bold text-sm border border-slate-200 dark:border-slate-700 shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-slate-100 text-sm">{{ $user->name }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-500 break-all">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div>
                        @if($user->is_active)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 uppercase tracking-wider">
                                Aktif
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40 uppercase tracking-wider">
                                Nonaktif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs">
                    <div class="flex items-center gap-2">
                        @if($user->role === 'admin')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/80 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/40 uppercase tracking-wider">
                                <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                            </span>
                        @elseif($user->role === 'operator')
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/40 uppercase tracking-wider">
                                <i class="fa-solid fa-user-gear mr-1"></i> Operator
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 uppercase tracking-wider">
                                <i class="fa-solid fa-screwdriver-wrench mr-1"></i> Teknisi
                            </span>
                        @endif

                        @if($user->phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $user->phone) }}" target="_blank" class="min-h-[38px] px-2.5 py-1 rounded-xl text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-400 font-mono text-xs flex items-center gap-1.5 border border-slate-200 dark:border-slate-700 transition">
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
                            class="min-h-[38px] px-3 py-1.5 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 rounded-xl transition border border-emerald-200 dark:border-emerald-800/50 flex items-center justify-center gap-1.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-400" 
                            title="Edit User">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>Edit</span>
                        </button>

                        @if($user->id !== auth()->id())
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="min-h-[38px] p-2 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 rounded-xl transition border border-rose-200 dark:border-rose-800/50 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-rose-400" title="Hapus User">
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
        <div class="p-4 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Create User -->
<div id="createModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-950/60 backdrop-blur-sm hidden p-0 sm:p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-t-3xl sm:rounded-2xl w-full max-w-md max-h-[92vh] sm:max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3 sticky top-0 bg-white dark:bg-slate-900 z-10">
            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-emerald-500"></i> Tambah User Baru
            </h3>
            <button onclick="closeCreateModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400" aria-label="Tutup modal"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" required class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" required class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">No WhatsApp / HP</label>
                <input type="text" name="phone" placeholder="08123456789" class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Role / Peran</label>
                <select name="role" required class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
                    <option value="technician">Teknisi (Petugas Lapangan)</option>
                    <option value="operator">Operator (Helpdesk)</option>
                    <option value="admin">Admin System</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <input type="password" name="password" required minlength="6" class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="closeCreateModal()" class="min-h-[42px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium border border-slate-200 dark:border-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400">Batal</button>
                <button type="submit" class="min-h-[42px] px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-sm shadow-emerald-600/20 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div id="editModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-slate-950/60 backdrop-blur-sm hidden p-0 sm:p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-t-3xl sm:rounded-2xl w-full max-w-md max-h-[92vh] sm:max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3 sticky top-0 bg-white dark:bg-slate-900 z-10">
            <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-emerald-500"></i> Edit Data User
            </h3>
            <button onclick="closeEditModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400" aria-label="Tutup modal"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" id="edit_name" required class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" id="edit_email" required class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">No WhatsApp / HP</label>
                <input type="text" name="phone" id="edit_phone" class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Role / Peran</label>
                    <select name="role" id="edit_role" required class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
                        <option value="technician">Teknisi</option>
                        <option value="operator">Operator</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status Akun</label>
                    <select name="is_active" id="edit_is_active" required class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Ganti Password (Opsional)</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="6" class="w-full min-h-[42px] bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="closeEditModal()" class="min-h-[42px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium border border-slate-200 dark:border-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400">Batal</button>
                <button type="submit" class="min-h-[42px] px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-sm shadow-emerald-600/20 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
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
