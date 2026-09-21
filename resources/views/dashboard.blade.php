@extends('layouts.app')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/80 dark:bg-slate-900/60 p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-white/10 shadow-sm dark:shadow-xl relative overflow-hidden backdrop-blur-sm transition-colors">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/20 dark:via-white/20 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex flex-wrap items-center gap-2 sm:gap-3">
                <span>Dashboard Management Tiket</span>
                @if($user->role === 'technician')
                    <span class="text-[11px] sm:text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full font-medium">Mode Teknisi</span>
                @else
                    <span class="text-[11px] sm:text-xs bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-300 dark:border-blue-500/30 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full font-medium">Admin / Operator (60 Billing)</span>
                @endif
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                @if($user->role === 'technician')
                    Menampilkan tiket yang ditugaskan khusus untuk akun Anda ({{ $user->name }}).
                @else
                    Mengelola seluruh tiket gangguan dari 60 aplikasi billing mitra terhubung.
                @endif
            </p>
        </div>
        @if(in_array($user->role, ['admin', 'operator']))
        <div>
            <button onclick="document.getElementById('createTicketModal').classList.remove('hidden')" 
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-blue-600/25 transition-all hover:scale-[1.02] border border-white/15 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px]">
                <i class="fa-solid fa-plus"></i> Tambah Tiket
            </button>
        </div>
        @endif
    </div>

    <!-- Stat Cards (2 cols on mobile, 4 cols on desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        <!-- Card Total -->
        <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 p-3.5 sm:p-5 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group hover:border-slate-300 dark:hover:border-white/20 transition-all">
            <div class="absolute -right-3 -bottom-3 text-slate-300/40 dark:text-slate-700/20 text-4xl sm:text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-ticket"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 tracking-wider">Total Tiket</div>
            <div class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mt-1 sm:mt-2">{{ number_format($totalCount) }}</div>
            <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 sm:mt-1 truncate">Tiket terdaftar</div>
        </div>

        <!-- Card Pending -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'pending'])) }}" 
           class="bg-white/80 dark:bg-slate-900/50 border {{ request('status') === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-amber-200 dark:border-amber-500/30' }} hover:border-amber-400 dark:hover:border-amber-500/60 p-3.5 sm:p-5 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-amber-400">
            <div class="absolute -right-3 -bottom-3 text-amber-500/10 text-4xl sm:text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-amber-700 dark:text-amber-300 tracking-wider">Pending</div>
            <div class="text-2xl sm:text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1 sm:mt-2">{{ number_format($pendingCount) }}</div>
            <div class="text-[10px] sm:text-xs text-amber-600/90 dark:text-amber-400/90 mt-0.5 sm:mt-1 truncate">Menunggu penugasan</div>
        </a>

        <!-- Card Process -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'process'])) }}" 
           class="bg-white/80 dark:bg-slate-900/50 border {{ request('status') === 'process' ? 'border-blue-500 ring-2 ring-blue-500/30' : 'border-blue-200 dark:border-blue-500/30' }} hover:border-blue-400 dark:hover:border-blue-500/60 p-3.5 sm:p-5 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-blue-400">
            <div class="absolute -right-3 -bottom-3 text-blue-500/10 text-4xl sm:text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-spinner"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-blue-700 dark:text-blue-300 tracking-wider">Proces</div>
            <div class="text-2xl sm:text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1 sm:mt-2">{{ number_format($processCount) }}</div>
            <div class="text-[10px] sm:text-xs text-blue-600/90 dark:text-blue-400/90 mt-0.5 sm:mt-1 truncate">Sedang dikerjakan</div>
        </a>

        <!-- Card Close -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'close'])) }}" 
           class="bg-white/80 dark:bg-slate-900/50 border {{ request('status') === 'close' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-emerald-200 dark:border-emerald-500/30' }} hover:border-emerald-400 dark:hover:border-emerald-500/60 p-3.5 sm:p-5 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400">
            <div class="absolute -right-3 -bottom-3 text-emerald-500/10 text-4xl sm:text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-circle-check"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300 tracking-wider">Close</div>
            <div class="text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 sm:mt-2">{{ number_format($closeCount) }}</div>
            <div class="text-[10px] sm:text-xs text-emerald-600/90 dark:text-emerald-400/90 mt-0.5 sm:mt-1 truncate">Selesai diperbaiki</div>
        </a>
    </div>

    <!-- Filters Section -->
    <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 p-4 sm:p-5 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden transition-colors">
        <form action="{{ route('dashboard') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Search -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Pencarian</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="No tiket, nama, no layanan..." 
                           class="w-full pl-9 pr-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Status Tiket</label>
                <select name="status" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="process" {{ request('status') === 'process' ? 'selected' : '' }}>Proces</option>
                    <option value="close" {{ request('status') === 'close' ? 'selected' : '' }}>Close</option>
                </select>
            </div>

            @if($user->role !== 'technician')
            <!-- Billing Instance Filter (Admin/Operator) -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Aplikasi Billing Mitra</label>
                <select name="billing_instance_id" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">Semua Billing (60)</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('billing_instance_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->tenant_code }} - {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Technician Filter (Admin/Operator) -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Teknisi Penanggung Jawab</label>
                <select name="technician_id" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">Semua Teknisi</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                            {{ $tech->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-span-full flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 pt-3 border-t border-slate-200 dark:border-white/5">
                <a href="{{ route('dashboard') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/70 dark:hover:bg-slate-700/70 dark:text-slate-300 text-xs font-medium rounded-xl border border-slate-300 dark:border-white/10 transition-all text-center focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[44px] flex items-center justify-center">Reset Filter</a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow-lg shadow-blue-600/25 border border-white/10 transition-all text-center focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px] flex items-center justify-center">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- Tickets Container -->
    <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 rounded-2xl shadow-sm dark:shadow-xl overflow-hidden transition-colors">
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm sm:text-base">
                    <i class="fa-solid fa-list-check text-blue-600 dark:text-blue-400"></i>
                    <span>Daftar Tiket Gangguan</span>
                </h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">Menampilkan {{ $tickets->count() }} dari {{ $tickets->total() }} tiket</span>
            </div>

            <!-- Export Buttons Group -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-400 dark:text-slate-500 hidden sm:inline mr-1"><i class="fa-solid fa-file-export mr-1"></i>Ekspor:</span>
                
                <!-- CSV Export -->
                <a href="{{ route('tickets.export.csv', request()->query()) }}" 
                   title="Ekspor ke CSV (Excel UTF-8)"
                   class="min-h-[40px] sm:min-h-[38px] px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-200 border border-slate-300 dark:border-white/10 text-xs font-semibold flex items-center gap-1.5 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400 shadow-sm">
                    <i class="fa-solid fa-file-csv text-emerald-600 dark:text-emerald-400 text-sm"></i>
                    <span>CSV</span>
                </a>

                <!-- Excel Export -->
                <a href="{{ route('tickets.export.excel', request()->query()) }}" 
                   title="Ekspor ke Microsoft Excel (.xlsx)"
                   class="min-h-[40px] sm:min-h-[38px] px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 text-xs font-semibold flex items-center gap-1.5 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400 shadow-sm">
                    <i class="fa-solid fa-file-excel text-emerald-600 dark:text-emerald-400 text-sm"></i>
                    <span>Excel</span>
                </a>

                <!-- PDF Export -->
                <a href="{{ route('tickets.export.pdf', request()->query()) }}" 
                   title="Ekspor ke Dokumen PDF Cetak"
                   class="min-h-[40px] sm:min-h-[38px] px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50 text-xs font-semibold flex items-center gap-1.5 transition-all focus:outline-none focus:ring-2 focus:ring-rose-400 shadow-sm">
                    <i class="fa-solid fa-file-pdf text-rose-600 dark:text-rose-400 text-sm"></i>
                    <span>PDF</span>
                </a>
            </div>
        </div>

        <!-- Desktop Table View (Visible on md screens and up) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-200/80 dark:border-white/10">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12">No</th>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">No Tiket & Billing Origin</th>
                        <th class="px-4 py-3.5">Pelanggan</th>
                        <th class="px-4 py-3.5">No Layanan</th>
                        <th class="px-4 py-3.5">Laporan & Detail</th>
                        <th class="px-4 py-3.5">Teknisi Assigned</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-white/5">
                    @forelse($tickets as $index => $ticket)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-4 py-4 text-center font-medium text-slate-500 dark:text-slate-400">
                                {{ $tickets->firstItem() + $index }}.
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900 dark:text-slate-200">{{ $ticket->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $ticket->created_at->format('H:i:s') }} WIB</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-bold text-blue-600 dark:text-blue-400">{{ $ticket->ticket_number }}</div>
                                <div class="inline-flex items-center gap-1 mt-1 text-[10px] bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 px-2 py-0.5 rounded-full text-slate-700 dark:text-slate-300">
                                    <i class="fa-solid fa-building text-[9px] text-indigo-500 dark:text-indigo-400"></i>
                                    <span>{{ $ticket->billingInstance->name ?? 'Unknown Billing' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900 dark:text-slate-200">{{ $ticket->customer_name }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $ticket->customer_phone ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-mono text-slate-700 dark:text-slate-300">
                                {{ $ticket->no_services }}
                            </td>
                            <td class="px-4 py-4 max-w-xs">
                                @if($ticket->category_name)
                                    <span class="text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-500/30 block w-fit mb-1">{{ $ticket->category_name }}</span>
                                @endif
                                <p class="line-clamp-2 text-slate-600 dark:text-slate-300 text-[11px]">{{ $ticket->problem_description }}</p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($ticket->assignedTechnician)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-emerald-50 dark:bg-emerald-500/20 border border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">
                                            <i class="fa-solid fa-user-gear"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $ticket->assignedTechnician->name }}</div>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $ticket->assignedTechnician->phone }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/15 border border-rose-200 dark:border-rose-500/30 px-2.5 py-1 rounded-full font-medium">
                                        <i class="fa-solid fa-triangle-exclamation text-[10px] text-rose-600 dark:text-rose-400"></i> Belum ditugaskan
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                @if($ticket->status === 'pending')
                                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 uppercase tracking-wider">Pending</span>
                                @elseif($ticket->status === 'process')
                                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-blue-50 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30 uppercase tracking-wider">Proces</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 uppercase tracking-wider">Close</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                <a href="{{ route('tickets.show', $ticket->id) }}" 
                                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white dark:bg-blue-600/20 dark:hover:bg-blue-600 dark:text-blue-300 dark:hover:text-white font-medium rounded-lg border border-blue-200 dark:border-blue-400/30 hover:border-blue-600 transition-all text-xs focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[36px]">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                <i class="fa-solid fa-inbox text-4xl mb-3 block text-slate-400 dark:text-slate-600"></i>
                                Tidak ada data tiket yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Touch Card List (Visible on mobile screens < md, antislop R-03) -->
        <div class="block md:hidden divide-y divide-slate-200 dark:divide-white/5">
            @forelse($tickets as $index => $ticket)
                <div class="p-4 space-y-3 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    <!-- Top Row: Ticket Number & Status -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-bold text-sm text-blue-600 dark:text-blue-400">
                            {{ $ticket->ticket_number }}
                        </div>
                        <div>
                            @if($ticket->status === 'pending')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 uppercase tracking-wider">Pending</span>
                            @elseif($ticket->status === 'process')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30 uppercase tracking-wider">Proces</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 uppercase tracking-wider">Close</span>
                            @endif
                        </div>
                    </div>

                    <!-- Customer & Billing -->
                    <div class="flex items-center justify-between text-xs">
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ $ticket->customer_name }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">{{ $ticket->no_services }}</div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 text-[10px] bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-white/10 px-2 py-0.5 rounded-md text-slate-700 dark:text-slate-300">
                                <i class="fa-solid fa-building text-[9px] text-indigo-500"></i>
                                {{ $ticket->billingInstance->name ?? 'Billing' }}
                            </span>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $ticket->created_at->format('d M Y, H:i') }}</div>
                        </div>
                    </div>

                    <!-- Category & Description -->
                    <div class="text-xs bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200/60 dark:border-white/5 space-y-1">
                        @if($ticket->category_name)
                            <span class="text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-500/30 inline-block">{{ $ticket->category_name }}</span>
                        @endif
                        <p class="text-slate-700 dark:text-slate-300 text-[11px] line-clamp-2 leading-relaxed">{{ $ticket->problem_description }}</p>
                    </div>

                    <!-- Technician & Action -->
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <div class="text-xs flex items-center gap-1.5">
                            @if($ticket->assignedTechnician)
                                <i class="fa-solid fa-user-gear text-emerald-600 dark:text-emerald-400 text-xs"></i>
                                <span class="font-medium text-emerald-700 dark:text-emerald-300 text-[11px]">{{ $ticket->assignedTechnician->name }}</span>
                            @else
                                <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Belum ditugaskan</span>
                            @endif
                        </div>
                        <a href="{{ route('tickets.show', $ticket->id) }}" 
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-xl text-xs shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px]">
                            <i class="fa-solid fa-eye"></i> Detail Tiket
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                    <i class="fa-solid fa-inbox text-3xl mb-2 block text-slate-400 dark:text-slate-600"></i>
                    Tidak ada tiket yang cocok dengan filter.
                </div>
            @endforelse
        </div>

        @if($tickets->hasPages())
            <div class="p-3 sm:p-4 border-t border-slate-200/80 dark:border-white/10 bg-slate-50/50 dark:bg-slate-950/60">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal Tambah Tiket (Responsive dialog, R-10 Primary Glass Accent 2) --}}
@if(in_array($user->role, ['admin', 'operator']))
<div id="createTicketModal" class="hidden fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-md" onclick="document.getElementById('createTicketModal').classList.add('hidden')"></div>
    
    {{-- Modal Content --}}
    <div class="relative bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-white/15 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto animate-modal backdrop-blur-xl transition-colors z-10">
        {{-- Header --}}
        <div class="sticky top-0 bg-white/95 dark:bg-slate-900/95 border-b border-slate-200 dark:border-white/10 p-4 sm:p-5 rounded-t-3xl flex items-center justify-between z-10 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 border border-white/20">
                    <i class="fa-solid fa-ticket text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Tambah Tiket Gangguan</h2>
                    <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Pilih pelanggan billing & buat tiket gangguan</p>
                </div>
            </div>
            <button onclick="document.getElementById('createTicketModal').classList.add('hidden')" 
                    class="w-10 h-10 sm:w-8 sm:h-8 rounded-xl sm:rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800/70 dark:hover:bg-slate-700 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all border border-slate-200 dark:border-white/10 focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[44px] sm:min-h-0"
                    aria-label="Tutup modal">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Form --}}
        <form action="{{ route('tickets.store') }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf

            {{-- Select & Cari Pelanggan dari Aplikasi Billing --}}
            <div class="bg-blue-50/50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-blue-200 dark:border-blue-500/30 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">
                        <i class="fa-solid fa-address-book text-blue-600 dark:text-blue-400 mr-1"></i> Data Pelanggan Billing
                    </label>
                    <span id="customerCountBadge" class="text-[10px] bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30 px-2 py-0.5 rounded-full font-medium">
                        {{ count($customersList) }} Pelanggan
                    </span>
                </div>

                {{-- Quick Search Input --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" id="customerSearchBox" oninput="filterCustomerSelectOptions(this.value)" 
                           placeholder="Cari No Layanan atau Nama..." 
                           class="w-full pl-9 pr-8 py-2.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <button type="button" onclick="clearCustomerSearchBox()" id="clearCustomerSearchBtn" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-700 dark:hover:text-white">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Select Dropdown --}}
                <select id="selectCustomerBilling" onchange="autoFillCustomerData(this)" 
                        class="w-full px-3 py-2.5 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">-Pilih Pelanggan-</option>
                    @foreach($customersList as $cust)
                        <option value="{{ $cust->no_services }}"
                                data-name="{{ $cust->customer_name }}"
                                data-phone="{{ $cust->customer_phone }}"
                                data-address="{{ $cust->customer_address }}"
                                data-billing="{{ $cust->billing_instance_id }}">
                            {{ $cust->no_services }} - {{ $cust->customer_name }} - Aktif
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Ketik pada kolom cari di atas untuk memfilter data secara instan.</p>
            </div>

            {{-- Billing Instance --}}
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between">
                    <span><i class="fa-solid fa-building text-indigo-600 dark:text-indigo-400 mr-1"></i> Aplikasi Billing Mitra <span class="text-rose-500 dark:text-rose-400">*</span></span>
                    <span id="customerLoadingState" class="hidden text-[10px] text-blue-600 dark:text-blue-400 font-normal lowercase flex items-center gap-1">
                        <i class="fa-solid fa-circle-notch fa-spin"></i> mengambil data billing...
                    </span>
                </label>
                <select name="billing_instance_id" required onchange="fetchCustomersFromSelectedBilling(this.value)"
                        class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">-- Pilih Billing Instance --</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ old('billing_instance_id') == $t->id ? 'selected' : '' }}>{{ $t->tenant_code }} - {{ $t->name }}</option>
                    @endforeach
                </select>
                @error('billing_instance_id') <span class="text-rose-500 dark:text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                {{-- Nama Pelanggan --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                        <i class="fa-solid fa-user text-blue-600 dark:text-blue-400 mr-1"></i> Nama Pelanggan <span class="text-rose-500 dark:text-rose-400">*</span>
                    </label>
                    <input type="text" name="customer_name" required value="{{ old('customer_name') }}" placeholder="Nama lengkap pelanggan"
                           class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    @error('customer_name') <span class="text-rose-500 dark:text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- No Layanan --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                        <i class="fa-solid fa-hashtag text-blue-600 dark:text-blue-400 mr-1"></i> No Layanan <span class="text-rose-500 dark:text-rose-400">*</span>
                    </label>
                    <input type="text" name="no_services" required value="{{ old('no_services') }}" placeholder="Nomor layanan pelanggan"
                           class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    @error('no_services') <span class="text-rose-500 dark:text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                {{-- No Telp --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                        <i class="fa-solid fa-phone text-emerald-600 dark:text-emerald-400 mr-1"></i> No Telp / WhatsApp
                    </label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="08xxxxxxxxxx"
                           class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                        <i class="fa-solid fa-tag text-indigo-600 dark:text-indigo-400 mr-1"></i> Kategori Gangguan
                    </label>
                    <input type="text" name="category_name" value="{{ old('category_name') }}" placeholder="Contoh: FO Putus, No Signal"
                           class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                </div>
            </div>

            {{-- Alamat --}}
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-location-dot text-rose-600 dark:text-rose-400 mr-1"></i> Alamat Pelanggan
                </label>
                <textarea name="customer_address" rows="2" placeholder="Alamat lengkap pelanggan"
                          class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">{{ old('customer_address') }}</textarea>
            </div>

            {{-- Deskripsi Masalah --}}
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-file-lines text-amber-600 dark:text-amber-400 mr-1"></i> Deskripsi Masalah <span class="text-rose-500 dark:text-rose-400">*</span>
                </label>
                <textarea name="problem_description" rows="3" required placeholder="Jelaskan detail gangguan yang dialami pelanggan..."
                          class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30">{{ old('problem_description') }}</textarea>
                @error('problem_description') <span class="text-rose-500 dark:text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Submit Controls (44px touch targets) --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 pt-3 sm:pt-4 border-t border-slate-200 dark:border-white/10">
                <button type="button" onclick="document.getElementById('createTicketModal').classList.add('hidden')" 
                        class="px-5 py-3 sm:py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl border border-slate-300 dark:border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[44px] flex items-center justify-center">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-3 sm:py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-600/25 border border-white/15 transition-all flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px]">
                    <i class="fa-solid fa-paper-plane"></i> Simpan Tiket
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .animate-modal {
        animation: modalIn 0.25s ease-out;
    }
</style>

<script id="customers-data" type="application/json">
{!! json_encode($customersList) !!}
</script>

<script>
// Close modal with Escape key (antislop R-32 keyboard accessibility)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('createTicketModal');
        if (modal && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }
    }
});

let allRawCustomers = JSON.parse(document.getElementById('customers-data')?.textContent || '[]');

function filterCustomerSelectOptions(query) {
    const customerSelect = document.getElementById('selectCustomerBilling');
    const clearBtn = document.getElementById('clearCustomerSearchBtn');
    const badge = document.getElementById('customerCountBadge');
    
    if (!customerSelect) return;
    
    if (clearBtn) {
        if (query && query.trim() !== '') {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }

    const q = (query || '').toLowerCase().trim();
    const filtered = allRawCustomers.filter(cust => {
        const noServ = (cust.no_services || '').toLowerCase();
        const name = (cust.customer_name || '').toLowerCase();
        const phone = (cust.customer_phone || '').toLowerCase();
        return noServ.includes(q) || name.includes(q) || phone.includes(q);
    });

    if (badge) {
        badge.textContent = filtered.length + ' Pelanggan';
    }

    customerSelect.innerHTML = '<option value="">-Pilih Pelanggan-</option>';
    filtered.forEach(cust => {
        const opt = document.createElement('option');
        opt.value = cust.no_services;
        opt.setAttribute('data-name', cust.customer_name || '');
        opt.setAttribute('data-phone', cust.customer_phone || '');
        opt.setAttribute('data-address', cust.customer_address || '');
        opt.setAttribute('data-billing', cust.billing_instance_id || '');
        opt.textContent = `${cust.no_services} - ${cust.customer_name} - ${cust.status || 'Aktif'}`;
        customerSelect.appendChild(opt);
    });
}

function clearCustomerSearchBox() {
    const input = document.getElementById('customerSearchBox');
    if (input) {
        input.value = '';
        filterCustomerSelectOptions('');
    }
}

function autoFillCustomerData(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    const noServices = selectedOption.value;
    const name = selectedOption.getAttribute('data-name') || '';
    const phone = selectedOption.getAttribute('data-phone') || '';
    const address = selectedOption.getAttribute('data-address') || '';
    const billingId = selectedOption.getAttribute('data-billing') || '';

    if (noServices) {
        const inputNoServ = document.querySelector('#createTicketModal input[name="no_services"]');
        if (inputNoServ) inputNoServ.value = noServices;
    }
    if (name) {
        const inputName = document.querySelector('#createTicketModal input[name="customer_name"]');
        if (inputName) inputName.value = name;
    }
    if (phone) {
        const inputPhone = document.querySelector('#createTicketModal input[name="customer_phone"]');
        if (inputPhone) inputPhone.value = phone;
    }
    if (address) {
        const inputAddr = document.querySelector('#createTicketModal textarea[name="customer_address"]');
        if (inputAddr) inputAddr.value = address;
    }
    if (billingId) {
        const selectBilling = document.querySelector('#createTicketModal select[name="billing_instance_id"]');
        if (selectBilling) selectBilling.value = billingId;
    }
}

function fetchCustomersFromSelectedBilling(billingId) {
    const customerSelect = document.getElementById('selectCustomerBilling');
    const loadingText = document.getElementById('customerLoadingState');

    if (!billingId) {
        billingId = 'all';
    }

    if (loadingText) loadingText.classList.remove('hidden');
    if (customerSelect) customerSelect.disabled = true;

    fetch('/billing-instances/' + billingId + '/customers')
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.customers)) {
                allRawCustomers = data.customers;
                const searchBox = document.getElementById('customerSearchBox');
                const q = searchBox ? searchBox.value : '';
                filterCustomerSelectOptions(q);
            }
        })
        .catch(err => console.error('Error fetching billing customers:', err))
        .finally(() => {
            if (loadingText) loadingText.classList.add('hidden');
            if (customerSelect) customerSelect.disabled = false;
        });
}
</script>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('createTicketModal').classList.remove('hidden');
    });
</script>
@endif
@endif
@endsection
