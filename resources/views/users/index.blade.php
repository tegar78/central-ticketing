@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-800/40 p-6 rounded-2xl border border-slate-700/50 backdrop-blur">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-users text-blue-400"></i> Manajemen User System
            </h1>
            <p class="text-slate-400 text-sm mt-1">
                Kelola akun Admin, Operator, dan Teknisi penanggung jawab 60 billing.
            </p>
        </div>
        <div>
            <button onclick="openCreateModal()" class="w-full md:w-auto px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-medium text-sm flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30 transition-all border border-blue-400/30">
                <i class="fa-solid fa-user-plus"></i> Tambah User Baru
            </button>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/60">
            <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Total User</div>
            <div class="text-2xl font-bold text-white mt-1">{{ $totalUsers }}</div>
        </div>
        <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/60">
            <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Admin</div>
            <div class="text-2xl font-bold text-purple-400 mt-1">{{ $adminCount }}</div>
        </div>
        <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/60">
            <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Operator</div>
            <div class="text-2xl font-bold text-blue-400 mt-1">{{ $operatorCount }}</div>
        </div>
        <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/60">
            <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Teknisi</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">{{ $technicianCount }}</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700/60">
        <form action="{{ route('users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Cari User</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, email, No WA..." class="w-full bg-slate-900/80 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-500 text-sm"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Role / Peran</label>
                <select name="role" class="w-full bg-slate-900/80 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition">
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="operator" {{ request('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                    <option value="technician" {{ request('role') == 'technician' ? 'selected' : '' }}>Teknisi</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Status Akun</label>
                <select name="status" class="w-full bg-slate-900/80 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-medium text-sm py-2 px-4 rounded-lg transition shadow-md">
                    Filter
                </button>
                <a href="{{ route('users.index') }}" class="bg-slate-700 hover:bg-slate-600 text-slate-300 font-medium text-sm py-2 px-4 rounded-lg transition text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-900/60 border-b border-slate-700/60 text-slate-400 uppercase tracking-wider text-xs">
                        <th class="py-4 px-6 font-semibold">User</th>
                        <th class="py-4 px-6 font-semibold">No WA / HP</th>
                        <th class="py-4 px-6 font-semibold">Role</th>
                        <th class="py-4 px-6 font-semibold">Status</th>
                        <th class="py-4 px-6 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-700 text-slate-200 flex items-center justify-center font-bold text-sm border border-slate-600">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-200">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-slate-300 font-mono text-xs">
                            {{ $user->phone ?? '-' }}
                        </td>
                        <td class="py-4 px-6">
                            @if($user->role === 'admin')
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                </span>
                            @elseif($user->role === 'operator')
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                    <i class="fa-solid fa-user-gear mr-1"></i> Operator
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    <i class="fa-solid fa-screwdriver-wrench mr-1"></i> Teknisi
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            @if($user->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                    Aktif
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditModal({{ json_encode($user) }})" class="p-2 text-slate-400 hover:text-blue-400 hover:bg-slate-700/50 rounded-lg transition" title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-slate-700/50 rounded-lg transition" title="Hapus User">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">
                            Tidak ada data user ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="p-4 border-t border-slate-700/60 bg-slate-900/40">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Create User -->
<div id="createModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-700 pb-3">
            <h3 class="font-bold text-lg text-white flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-blue-400"></i> Tambah User Baru
            </h3>
            <button onclick="closeCreateModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" name="name" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email</label>
                <input type="email" name="email" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">No WhatsApp / HP</label>
                <input type="text" name="phone" placeholder="08123456789" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Role / Peran</label>
                <select name="role" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                    <option value="technician">Teknisi (Petugas Lapangan)</option>
                    <option value="operator">Operator (Helpdesk)</option>
                    <option value="admin">Admin System</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required minlength="6" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-700">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow-md">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-700 pb-3">
            <h3 class="font-bold text-lg text-white flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-blue-400"></i> Edit Data User
            </h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="edit_name" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email</label>
                <input type="email" name="email" id="edit_email" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">No WhatsApp / HP</label>
                <input type="text" name="phone" id="edit_phone" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Role / Peran</label>
                    <select name="role" id="edit_role" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="technician">Teknisi</option>
                        <option value="operator">Operator</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Status Akun</label>
                    <select name="is_active" id="edit_is_active" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Ganti Password (Opsional)</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="6" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-700">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow-md">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }
    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
    }

    function openEditModal(user) {
        document.getElementById('editForm').action = "/users/" + user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_is_active').value = user.is_active ? "1" : "0";

        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection
