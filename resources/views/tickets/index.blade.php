@extends('layouts.app')

@section('title', 'Direktori Tiket')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden transition-colors">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-transparent"></div>
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                    @if($user->role === 'technician')
                    Daftar Penugasan Teknisi
                    @else
                    Pusat Manajemen Tiket
                    @endif
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-list-check text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <span>Direktori Tiket Gangguan</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                @if($user->role === 'technician')
                Menampilkan tiket gangguan yang ditugaskan kepada Anda ({{ $user->name }}).
                @else
                Monitoring, penugasan teknisi, dan penyelesaian tiket gangguan dari seluruh server billing Gayuh.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm rounded-xl transition-all border border-slate-200/60 dark:border-slate-700">
                <i class="fa-solid fa-chart-pie text-xs"></i>
                <span>Dashboard & Statistik</span>
            </a>

            @if(in_array($user->role, ['admin', 'operator']))
            <button type="button" onclick="document.getElementById('createTicketModal').classList.remove('hidden')"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm rounded-xl shadow-md shadow-emerald-600/25 transition-all hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Tiket Baru</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Quick Status Pill Navigation -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
        <a href="{{ route('tickets.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ !request()->has('status') ? 'bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500 text-emerald-700 dark:text-emerald-300 shadow-sm' : 'bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-emerald-500 hover:text-emerald-600' }}">
            <i class="fa-solid fa-ticket text-xs"></i>
            <span>Semua Tiket</span>
            <span class="px-1.5 py-0.5 {{ !request()->has('status') ? 'bg-emerald-200 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }} rounded-md text-[10px] font-bold">{{ number_format($totalCount) }}</span>
        </a>

        <a href="{{ route('tickets.index', ['status' => 'pending']) }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'pending' ? 'bg-sky-50 dark:bg-sky-950/40 border-2 border-sky-500 text-sky-700 dark:text-sky-300 shadow-sm' : 'bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-sky-500 hover:text-sky-600' }}">
            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
            <span>Pending</span>
            <span class="px-1.5 py-0.5 {{ request('status') === 'pending' ? 'bg-sky-200 dark:bg-sky-900/60 text-sky-800 dark:text-sky-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }} rounded-md text-[10px] font-bold">{{ number_format($pendingCount) }}</span>
        </a>

        <a href="{{ route('tickets.index', ['status' => 'process']) }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'process' ? 'bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-500 text-amber-700 dark:text-amber-300 shadow-sm' : 'bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-amber-500 hover:text-amber-600' }}">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span>Dalam Proses</span>
            <span class="px-1.5 py-0.5 {{ request('status') === 'process' ? 'bg-amber-200 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }} rounded-md text-[10px] font-bold">{{ number_format($processCount) }}</span>
        </a>

        <a href="{{ route('tickets.index', ['status' => 'close']) }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'close' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500 text-emerald-700 dark:text-emerald-300 shadow-sm' : 'bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:border-emerald-500 hover:text-emerald-600' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Selesai</span>
            <span class="px-1.5 py-0.5 {{ request('status') === 'close' ? 'bg-emerald-200 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }} rounded-md text-[10px] font-bold">{{ number_format($closeCount) }}</span>
        </a>
    </div>

    <!-- Filters Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm transition-colors">
        <form action="{{ route('tickets.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Search -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1.5">Pencarian</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fa-solid fa-magnifying-glass text-xs"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="No tiket, nama, layanan..."
                        class="w-full pl-8 pr-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 min-h-[40px] transition-all">
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1.5">Status Tiket</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 min-h-[40px] transition-all">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="process" {{ request('status') === 'process' ? 'selected' : '' }}>Dalam Proses</option>
                    <option value="close" {{ request('status') === 'close' ? 'selected' : '' }}>Close</option>
                </select>
            </div>

            @if($user->role !== 'technician')
            <!-- Billing Instance Filter -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1.5">Aplikasi Billing Gayuh</label>
                <select name="billing_instance_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 min-h-[40px] transition-all">
                    <option value="">Semua Billing</option>
                    @foreach($tenants as $t)
                    <option value="{{ $t->id }}" {{ request('billing_instance_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->tenant_code }} - {{ $t->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Technician Filter -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1.5">Teknisi Penanggung Jawab</label>
                <select name="technician_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 min-h-[40px] transition-all">
                    <option value="">Semua Teknisi</option>
                    @foreach($technicians as $tech)
                    <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                        {{ $tech->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-span-full flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('tickets.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all text-center">Reset</a>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all text-center">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- Tickets Directory Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-colors">
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm sm:text-base">
                    <i class="fa-solid fa-list-check text-emerald-600 dark:text-emerald-400"></i>
                    <span>Daftar Tiket Gangguan</span>
                </h3>
                <span class="text-xs text-slate-400 dark:text-slate-500">Menampilkan {{ $tickets->count() }} dari total {{ $tickets->total() }} tiket</span>
            </div>

            <!-- Export Buttons -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 hidden sm:inline mr-1"><i class="fa-solid fa-file-export mr-1"></i>Ekspor:</span>
                <a href="{{ route('tickets.export.csv', request()->query()) }}"
                    title="Ekspor ke CSV"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-file-csv text-emerald-600 dark:text-emerald-400"></i>
                    <span>CSV</span>
                </a>
                <a href="{{ route('tickets.export.excel', request()->query()) }}"
                    title="Ekspor ke Excel"
                    class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 dark:text-emerald-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-file-excel text-emerald-600 dark:text-emerald-400"></i>
                    <span>Excel</span>
                </a>
                <a href="{{ route('tickets.export.pdf', request()->query()) }}"
                    target="_blank"
                    title="Ekspor ke PDF"
                    class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 dark:text-rose-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-file-pdf text-rose-600 dark:text-rose-400"></i>
                    <span>PDF</span>
                </a>
            </div>
        </div>

        <!-- Mobile Card View (< md screens) -->
        <div class="block md:hidden divide-y divide-slate-100 dark:divide-slate-800/60">
            @forelse($tickets as $ticket)
            <div class="p-4 space-y-2.5 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                <div class="flex items-center justify-between gap-2">
                    <a href="{{ route('tickets.show', $ticket->id) }}" class="font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <i class="fa-solid fa-ticket text-[10px]"></i>
                        <span>{{ $ticket->ticket_number }}</span>
                    </a>
                    @if($ticket->status === 'pending')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Pending
                    </span>
                    @elseif($ticket->status === 'process')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Diproses
                    </span>
                    @elseif($ticket->status === 'close')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                        {{ ucfirst($ticket->status) }}
                    </span>
                    @endif
                </div>

                <div>
                    <div class="font-bold text-slate-900 dark:text-white text-xs">{{ $ticket->customer_name }}</div>
                    <div class="text-[11px] text-slate-400 font-mono mt-0.5">ID: {{ $ticket->no_services ?? '-' }} &bull; {{ $ticket->billingInstance->name ?? '-' }}</div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 text-xs">
                    <div class="font-semibold text-slate-700 dark:text-slate-300 text-[11px]">{{ $ticket->category_name ?? 'Gangguan Umum' }}</div>
                    <div class="text-slate-500 dark:text-slate-400 text-[11px] line-clamp-2 mt-0.5">{{ $ticket->problem_description }}</div>
                </div>

                <div class="flex items-center justify-between text-[11px] pt-1 text-slate-400">
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-user-gear text-emerald-500 text-[10px]"></i>
                        <span>{{ $ticket->assignedTechnician->name ?? 'Belum ditugaskan' }}</span>
                    </div>
                    <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold px-2 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 transition-colors">
                        <span>Detail</span>
                        <i class="fa-solid fa-arrow-right text-[9px]"></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-slate-400 dark:text-slate-500 text-xs">
                Belum ada tiket gangguan yang ditemukan.
            </div>
            @endforelse
        </div>

        <!-- Desktop Table View (>= md screens) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">No. Tiket</th>
                        <th class="py-3 px-4">Pelanggan</th>
                        <th class="py-3 px-4">Billing Gayuh</th>
                        <th class="py-3 px-4">Kategori & Masalah</th>
                        <th class="py-3 px-4">Teknisi</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($tickets as $ticket)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">
                            <a href="{{ route('tickets.show', $ticket->id) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-1.5">
                                {{ $ticket->ticket_number }}
                            </a>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900 dark:text-white">{{ $ticket->customer_name }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">ID: {{ $ticket->no_services ?? '-' }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $ticket->billingInstance->tenant_code ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 max-w-xs truncate">
                            <div class="font-semibold text-slate-700 dark:text-slate-300">{{ $ticket->category_name ?? 'Gangguan Umum' }}</div>
                            <div class="text-[11px] text-slate-400 truncate">{{ $ticket->problem_description }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($ticket->assignedTechnician)
                            <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                                <i class="fa-solid fa-user-gear text-emerald-500 text-[10px]"></i>
                                <span>{{ $ticket->assignedTechnician->name }}</span>
                            </div>
                            @else
                            <span class="text-slate-400 italic">Belum ditugaskan</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
                            {{ $ticket->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($ticket->status === 'pending')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Pending
                            </span>
                            @elseif($ticket->status === 'process')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Diproses
                            </span>
                            @elseif($ticket->status === 'close')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                {{ ucfirst($ticket->status) }}
                            </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <a href="{{ route('tickets.show', $ticket->id) }}"
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 font-semibold transition-all">
                                <span>Detail</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400 dark:text-slate-500">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                <i class="fa-solid fa-inbox text-lg"></i>
                            </div>
                            <p class="font-semibold text-xs">Belum ada tiket gangguan yang ditemukan.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Coba sesuaikan filter pencarian atau buat tiket baru.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $tickets->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Dialog Tambah Tiket Baru (dengan Live Search) -->
@if(in_array($user->role, ['admin', 'operator']))
<div id="createTicketModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 animate-modal">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-ticket text-sm"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Tambah Tiket Baru</h3>
            </div>
            <button type="button" onclick="document.getElementById('createTicketModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" class="space-y-4" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn){ btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin mr-1.5\'></i> Menyimpan...'; }">
            @csrf

            <!-- Hidden coordinates for auto-fill -->
            <input type="hidden" name="latitude" id="customerLatitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="customerLongitude" value="{{ old('longitude') }}">

            <!-- Pilih Pelanggan (Modern Live Search) -->
            <div class="relative" id="customerLiveSearchContainer">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400">
                        Pilih Dari Database Pelanggan
                    </label>
                    <span id="customerCountBadge" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200/60 dark:border-emerald-800/40">
                        {{ number_format($totalCustomersCount ?? 0) }} Pelanggan
                    </span>
                </div>

                <!-- Input Box -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" id="customerSearchBox" 
                        placeholder="Ketik nama atau ID pelanggan (contoh: ahmad)..." 
                        autocomplete="off"
                        onfocus="handleCustomerSearchFocus()"
                        oninput="handleCustomerLiveSearch(this.value)"
                        onkeydown="handleCustomerSearchKeydown(event)"
                        class="w-full pl-8 pr-8 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 transition-all">
                    
                    <button type="button" id="clearCustomerSearchBtn" onclick="clearCustomerSearchBox()" 
                        class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Selected Customer Card -->
                <div id="selectedCustomerBanner" class="hidden mt-2 p-2.5 bg-emerald-50/90 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 rounded-xl flex items-center justify-between text-xs animate-fade-in shadow-sm">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 text-xs shadow-sm shadow-emerald-500/30">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <div class="truncate">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span id="selectedCustName" class="font-bold text-emerald-900 dark:text-emerald-200"></span>
                                <span id="selectedCustId" class="text-[10px] text-emerald-700 dark:text-emerald-300 font-mono bg-emerald-100 dark:bg-emerald-900/60 px-1.5 py-0.5 rounded"></span>
                            </div>
                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-2 mt-0.5">
                                <span id="selectedCustPhone"></span>
                                <span id="selectedCustOdp" class="hidden font-mono text-[10px] bg-slate-200/60 dark:bg-slate-800 px-1 rounded"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="resetCustomerSelection()" 
                        title="Ganti atau hapus pilihan pelanggan"
                        class="ml-2 px-2.5 py-1 text-[11px] font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg border border-rose-200 dark:border-rose-900/40 transition-colors flex-shrink-0 flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i> Ganti
                    </button>
                </div>

                <!-- Floating Dropdown Live Search Results -->
                <div id="customerLiveSearchResults" 
                    class="hidden absolute z-50 left-0 right-0 mt-1.5 max-h-64 overflow-y-auto bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-700/60"
                    style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">
                </div>
            </div>

            <!-- Billing Instance -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Aplikasi Billing Gayuh <span class="text-rose-500">*</span></label>
                <select name="billing_instance_id" required onchange="fetchCustomersFromSelectedBilling(this.value)"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    <option value="">-- Pilih Billing Instance --</option>
                    @foreach($tenants as $t)
                    <option value="{{ $t->id }}" {{ old('billing_instance_id') == $t->id ? 'selected' : '' }}>{{ $t->tenant_code }} - {{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Nama Pelanggan <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_name" required value="{{ old('customer_name') }}" placeholder="Nama pelanggan"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">No. Layanan / ID <span class="text-rose-500">*</span></label>
                    <input type="text" name="no_services" required value="{{ old('no_services') }}" placeholder="Contoh: 00141"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">No. Telp / WA</label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="08xxxxxxxxxx"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Kategori Masalah</label>
                    <input type="text" name="category_name" value="{{ old('category_name') }}" placeholder="Contoh: Kabel FO Putus"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Alamat Pelanggan</label>
                <textarea name="customer_address" rows="2" placeholder="Alamat lengkap pelanggan"
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">{{ old('customer_address') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Deskripsi Laporan Masalah <span class="text-rose-500">*</span></label>
                <textarea name="problem_description" rows="3" required placeholder="Jelaskan detail kendala..."
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">{{ old('problem_description') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('createTicketModal').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-600/20">
                    Simpan & Buat Tiket
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('createTicketModal');
            if (modal && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        }
    });

    let currentSearchResults = [];
    let activeHighlightedIndex = -1;
    let liveSearchDebounceTimer = null;
    let liveSearchAbortController = null;

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function highlightKeyword(text, keyword) {
        if (!text) return '';
        if (!keyword) return escapeHtml(text);
        const safeText = escapeHtml(text);
        const safeKw = keyword.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        if (!safeKw) return safeText;
        const regex = new RegExp(`(${safeKw})`, 'gi');
        return safeText.replace(regex, '<mark class="bg-amber-200 dark:bg-amber-900/60 text-amber-950 dark:text-amber-200 font-semibold px-0.5 rounded">$1</mark>');
    }

    function handleCustomerLiveSearch(query) {
        const clearBtn = document.getElementById('clearCustomerSearchBtn');
        const resultsBox = document.getElementById('customerLiveSearchResults');
        const badge = document.getElementById('customerCountBadge');

        if (clearBtn) {
            if (query && query.trim() !== '') {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        }

        clearTimeout(liveSearchDebounceTimer);

        const q = (query || '').trim();
        if (q === '') {
            if (resultsBox) resultsBox.classList.add('hidden');
            if (badge) badge.textContent = '{{ number_format($totalCustomersCount ?? 0) }} Pelanggan';
            currentSearchResults = [];
            activeHighlightedIndex = -1;
            return;
        }

        liveSearchDebounceTimer = setTimeout(() => {
            performCustomerSearch(q);
        }, 180);
    }

    function performCustomerSearch(query) {
        const resultsBox = document.getElementById('customerLiveSearchResults');
        const badge = document.getElementById('customerCountBadge');
        if (!resultsBox) return;

        if (liveSearchAbortController) {
            liveSearchAbortController.abort();
        }
        liveSearchAbortController = new AbortController();

        resultsBox.innerHTML = `
            <div class="p-3 text-center text-xs text-slate-500 dark:text-slate-400">
                <i class="fa-solid fa-spinner fa-spin mr-1.5 text-emerald-500"></i> Mencari database pelanggan...
            </div>
        `;
        resultsBox.classList.remove('hidden');

        fetch(`{{ route('customers.liveSearch') }}?q=${encodeURIComponent(query)}`, {
            signal: liveSearchAbortController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            currentSearchResults = data.customers || [];
            activeHighlightedIndex = -1;

            if (badge) {
                badge.textContent = `${data.total} Ditemukan`;
            }

            if (currentSearchResults.length === 0) {
                resultsBox.innerHTML = `
                    <div class="p-4 text-center text-xs text-slate-500 dark:text-slate-400">
                        <i class="fa-solid fa-user-slash text-slate-300 dark:text-slate-600 text-lg mb-1 block"></i>
                        Tidak ditemukan pelanggan dengan kata kunci "<strong>${escapeHtml(query)}</strong>"
                    </div>
                `;
                resultsBox.classList.remove('hidden');
                return;
            }

            const visibleItems = currentSearchResults.slice(0, 20);
            let html = '';
            visibleItems.forEach((cust, index) => {
                const nameHtml = highlightKeyword(cust.customer_name || 'Tanpa Nama', query);
                const idHtml = highlightKeyword(cust.no_services || '', query);
                const phoneHtml = highlightKeyword(cust.customer_phone || '-', query);
                const statusClass = (cust.status || '').toLowerCase() === 'active' || (cust.status || '').toLowerCase() === 'aktif'
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40'
                    : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40';

                html += `
                    <div class="live-search-item px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700/60 cursor-pointer transition-colors flex items-center justify-between gap-3 text-xs"
                         data-index="${index}"
                         onclick="selectCustomerByIndex(${index})">
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/80 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 text-xs">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="truncate">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-slate-800 dark:text-slate-100 text-xs">${nameHtml}</span>
                                    <span class="font-mono text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-1.5 py-0.5 rounded font-semibold border border-slate-200 dark:border-slate-600">${idHtml}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                    <span><i class="fa-solid fa-phone text-[9px] mr-1"></i>${phoneHtml}</span>
                                    ${cust.odp_name && cust.odp_name !== '-' ? `<span class="text-slate-400 dark:text-slate-500">•</span><span class="font-mono text-[10px] text-indigo-600 dark:text-indigo-400"><i class="fa-solid fa-network-wired text-[9px] mr-0.5"></i>${escapeHtml(cust.odp_name)}</span>` : ''}
                                    ${cust.package_name && cust.package_name !== '-' ? `<span class="text-slate-400 dark:text-slate-500">•</span><span>${escapeHtml(cust.package_name)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            ${cust.status ? `<span class="text-[9px] uppercase tracking-wider font-semibold px-1.5 py-0.5 rounded-full ${statusClass}">${escapeHtml(cust.status)}</span>` : ''}
                            <i class="fa-solid fa-chevron-right text-slate-300 dark:text-slate-600 text-[10px]"></i>
                        </div>
                    </div>
                `;
            });

            if (currentSearchResults.length > 20) {
                html += `
                    <div class="px-3 py-2 bg-slate-50 dark:bg-slate-800/90 text-center text-[11px] text-slate-500 dark:text-slate-400">
                        Menampilkan <strong>20</strong> dari <strong>${currentSearchResults.length}</strong> pelanggan. Ketik lebih spesifik jika belum terlihat.
                    </div>
                `;
            }

            resultsBox.innerHTML = html;
            resultsBox.classList.remove('hidden');
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                console.error('Error fetching customers:', err);
                resultsBox.innerHTML = `
                    <div class="p-3 text-center text-xs text-rose-500">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Gagal memuat data pelanggan.
                    </div>
                `;
            }
        });
    }

    function handleCustomerSearchFocus() {
        const input = document.getElementById('customerSearchBox');
        if (input && input.value.trim() !== '') {
            performCustomerSearch(input.value.trim());
        }
    }

    function handleCustomerSearchKeydown(e) {
        const resultsBox = document.getElementById('customerLiveSearchResults');
        if (!resultsBox || resultsBox.classList.contains('hidden')) return;

        const items = resultsBox.querySelectorAll('.live-search-item');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeHighlightedIndex = (activeHighlightedIndex + 1) % items.length;
            updateHighlightedItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeHighlightedIndex = (activeHighlightedIndex - 1 + items.length) % items.length;
            updateHighlightedItem(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeHighlightedIndex >= 0 && activeHighlightedIndex < items.length) {
                selectCustomerByIndex(activeHighlightedIndex);
            } else if (items.length > 0) {
                selectCustomerByIndex(0);
            }
        } else if (e.key === 'Escape') {
            resultsBox.classList.add('hidden');
        }
    }

    function updateHighlightedItem(items) {
        items.forEach((item, idx) => {
            if (idx === activeHighlightedIndex) {
                item.classList.add('bg-emerald-50', 'dark:bg-slate-700', 'border-l-4', 'border-emerald-500');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('bg-emerald-50', 'dark:bg-slate-700', 'border-l-4', 'border-emerald-500');
            }
        });
    }

    function selectCustomerByIndex(index) {
        if (index < 0 || index >= currentSearchResults.length) return;
        const cust = currentSearchResults[index];
        applyCustomerSelection(cust);
    }

    function applyCustomerSelection(cust) {
        if (!cust) return;

        const inputNoServ = document.querySelector('#createTicketModal input[name="no_services"]');
        if (inputNoServ) inputNoServ.value = cust.no_services || '';

        const inputName = document.querySelector('#createTicketModal input[name="customer_name"]');
        if (inputName) inputName.value = cust.customer_name || '';

        const inputPhone = document.querySelector('#createTicketModal input[name="customer_phone"]');
        if (inputPhone) inputPhone.value = cust.customer_phone || '';

        const inputAddr = document.querySelector('#createTicketModal textarea[name="customer_address"]');
        if (inputAddr) inputAddr.value = cust.customer_address || '';

        const selectBilling = document.querySelector('#createTicketModal select[name="billing_instance_id"]');
        if (selectBilling && cust.billing_instance_id) {
            selectBilling.value = cust.billing_instance_id;
        }

        const inputLat = document.getElementById('customerLatitude');
        if (inputLat && cust.latitude) inputLat.value = cust.latitude;

        const inputLng = document.getElementById('customerLongitude');
        if (inputLng && cust.longitude) inputLng.value = cust.longitude;

        const banner = document.getElementById('selectedCustomerBanner');
        const nameEl = document.getElementById('selectedCustName');
        const idEl = document.getElementById('selectedCustId');
        const phoneEl = document.getElementById('selectedCustPhone');
        const odpEl = document.getElementById('selectedCustOdp');

        if (nameEl) nameEl.textContent = cust.customer_name || 'Tanpa Nama';
        if (idEl) idEl.textContent = cust.no_services ? `ID: ${cust.no_services}` : '';
        if (phoneEl) phoneEl.textContent = cust.customer_phone ? `WA: ${cust.customer_phone}` : '';
        if (odpEl) {
            if (cust.odp_name) {
                odpEl.textContent = `ODP: ${cust.odp_name}`;
                odpEl.classList.remove('hidden');
            } else {
                odpEl.classList.add('hidden');
            }
        }
        if (banner) banner.classList.remove('hidden');

        const searchBox = document.getElementById('customerSearchBox');
        if (searchBox) {
            searchBox.value = `${cust.no_services || ''} - ${cust.customer_name || ''}`;
        }

        const clearBtn = document.getElementById('clearCustomerSearchBtn');
        if (clearBtn) clearBtn.classList.remove('hidden');

        const resultsBox = document.getElementById('customerLiveSearchResults');
        if (resultsBox) resultsBox.classList.add('hidden');
    }

    function resetCustomerSelection() {
        const banner = document.getElementById('selectedCustomerBanner');
        if (banner) banner.classList.add('hidden');

        const searchBox = document.getElementById('customerSearchBox');
        if (searchBox) {
            searchBox.value = '';
            searchBox.focus();
        }

        const clearBtn = document.getElementById('clearCustomerSearchBtn');
        if (clearBtn) clearBtn.classList.add('hidden');

        const resultsBox = document.getElementById('customerLiveSearchResults');
        if (resultsBox) resultsBox.classList.add('hidden');

        const badge = document.getElementById('customerCountBadge');
        if (badge) badge.textContent = allRawCustomers.length + ' Pelanggan';

        const inputNoServ = document.querySelector('#createTicketModal input[name="no_services"]');
        if (inputNoServ) inputNoServ.value = '';

        const inputName = document.querySelector('#createTicketModal input[name="customer_name"]');
        if (inputName) inputName.value = '';

        const inputPhone = document.querySelector('#createTicketModal input[name="customer_phone"]');
        if (inputPhone) inputPhone.value = '';

        const inputAddr = document.querySelector('#createTicketModal textarea[name="customer_address"]');
        if (inputAddr) inputAddr.value = '';

        const inputLat = document.getElementById('customerLatitude');
        if (inputLat) inputLat.value = '';

        const inputLng = document.getElementById('customerLongitude');
        if (inputLng) inputLng.value = '';
    }

    function clearCustomerSearchBox() {
        resetCustomerSelection();
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('customerLiveSearchContainer');
        const resultsBox = document.getElementById('customerLiveSearchResults');
        if (container && resultsBox && !container.contains(e.target)) {
            resultsBox.classList.add('hidden');
        }
    });

    function fetchCustomersFromSelectedBilling(billingId) {
        if (!billingId) billingId = 'all';

        const searchBox = document.getElementById('customerSearchBox');
        const badge = document.getElementById('customerCountBadge');

        if (badge) badge.textContent = 'Memuat data...';

        fetch('/billing-instances/' + billingId + '/customers')
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.customers)) {
                    allRawCustomers = data.customers;
                    if (badge) badge.textContent = allRawCustomers.length + ' Pelanggan';
                    const q = searchBox ? searchBox.value.trim() : '';
                    if (q !== '') {
                        performCustomerSearch(q);
                    }
                }
            })
            .catch(err => {
                console.error('Error fetching billing customers:', err);
                if (badge) badge.textContent = allRawCustomers.length + ' Pelanggan';
            });
    }
</script>

@if(isset($errors) && $errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('createTicketModal')?.classList.remove('hidden');
    });
</script>
@endif
@endif
@endsection
