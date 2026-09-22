@extends('layouts.app')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Header Banner (Figma Reference) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                    @if($user->role === 'technician')
                        Portal Teknisi
                    @else
                        Admin & Operator • 60 Portal Billing
                    @endif
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Dashboard Dukungan Tiket
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                @if($user->role === 'technician')
                    Menampilkan tiket gangguan yang ditugaskan kepada Anda ({{ $user->name }}).
                @else
                    Monitoring, penugasan teknisi, dan penyelesaian tiket gangguan dari seluruh jaringan billing Gayuh.
                @endif
            </p>
        </div>

        @if(in_array($user->role, ['admin', 'operator']))
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('createTicketModal').classList.remove('hidden')" 
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm rounded-xl shadow-md shadow-emerald-600/25 transition-all hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Tiket Baru</span>
            </button>
        </div>
        @endif
    </div>

    <!-- 4 KPI Stat Cards (Figma Reference: Open, New, In Process, Closed) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        <!-- Card 1: Total Tiket -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group hover:border-emerald-300 dark:hover:border-emerald-700/60 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-slate-400 dark:text-slate-500 tracking-wider">Total Tiket</span>
                <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-ticket"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2 sm:mt-3">{{ number_format($totalCount) }}</div>
            <div class="flex items-center gap-1.5 mt-2 text-xs">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <i class="fa-solid fa-arrow-trend-up text-[8px]"></i> 100%
                </span>
                <span class="text-slate-400 dark:text-slate-500 text-[11px]">Seluruh tiket</span>
            </div>
        </div>

        <!-- Card 2: Pending (Tiket Baru) -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'pending'])) }}" 
           class="bg-white dark:bg-slate-900 border {{ request('status') === 'pending' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-sky-400 dark:hover:border-sky-600 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-sky-600 dark:text-sky-400 tracking-wider">Pending (Baru)</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-sky-600 dark:text-sky-400 mt-2 sm:mt-3">{{ number_format($pendingCount) }}</div>
            <div class="flex items-center gap-1.5 mt-2 text-xs">
                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                    Menunggu
                </span>
                <span class="text-slate-400 dark:text-slate-500 text-[11px]">Penugasan teknisi</span>
            </div>
        </a>

        <!-- Card 3: Process -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'process'])) }}" 
           class="bg-white dark:bg-slate-900 border {{ request('status') === 'process' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-amber-400 dark:hover:border-amber-600 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-amber-600 dark:text-amber-400 tracking-wider">Dalam Proses</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-spinner"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2 sm:mt-3">{{ number_format($processCount) }}</div>
            <div class="flex items-center gap-1.5 mt-2 text-xs">
                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                    Pengerjaan
                </span>
                <span class="text-slate-400 dark:text-slate-500 text-[11px]">Oleh teknisi</span>
            </div>
        </a>

        <!-- Card 4: Selesai (Close) -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'close'])) }}" 
           class="bg-white dark:bg-slate-900 border {{ request('status') === 'close' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-emerald-400 dark:hover:border-emerald-600 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-emerald-600 dark:text-emerald-400 tracking-wider">Selesai (Close)</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2 sm:mt-3">{{ number_format($closeCount) }}</div>
            <div class="flex items-center gap-1.5 mt-2 text-xs">
                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    Terselesaikan
                </span>
                <span class="text-slate-400 dark:text-slate-500 text-[11px]">Gangguan tuntas</span>
            </div>
        </a>
    </div>

    <!-- Visual Analytics Section (Figma Reference: Ticket Volumes & Activity Trends) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Ticket Volumes Donut Chart (5 cols) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">Komposisi Volume Tiket</h3>
                    <span class="text-xs text-slate-400"><i class="fa-solid fa-chart-pie mr-1"></i>Distribusi</span>
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">Perbandingan status tiket yang tercatat pada sistem saat ini.</p>
                
                <div class="relative h-56 flex items-center justify-center">
                    <canvas id="ticketVolumesChart"
                            data-pending="{{ $pendingCount }}"
                            data-process="{{ $processCount }}"
                            data-close="{{ $closeCount }}"
                            data-total="{{ max(1, $totalCount) }}"></canvas>
                </div>
            </div>

            <!-- Legend Breakdown -->
            <div class="grid grid-cols-3 gap-2 pt-4 mt-2 border-t border-slate-100 dark:border-slate-800 text-center">
                <div class="p-2 rounded-xl bg-sky-50/50 dark:bg-sky-950/20">
                    <div class="text-[10px] text-sky-600 font-bold uppercase">Pending</div>
                    <div class="text-sm font-extrabold text-sky-700 dark:text-sky-300">{{ number_format($pendingCount) }}</div>
                </div>
                <div class="p-2 rounded-xl bg-amber-50/50 dark:bg-amber-950/20">
                    <div class="text-[10px] text-amber-600 font-bold uppercase">Proses</div>
                    <div class="text-sm font-extrabold text-amber-700 dark:text-amber-300">{{ number_format($processCount) }}</div>
                </div>
                <div class="p-2 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20">
                    <div class="text-[10px] text-emerald-600 font-bold uppercase">Close</div>
                    <div class="text-sm font-extrabold text-emerald-700 dark:text-emerald-300">{{ number_format($closeCount) }}</div>
                </div>
            </div>
        </div>

        <!-- Weekly Activity Trend Chart (7 cols) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">Tren Penanganan Tiket</h3>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold"><i class="fa-solid fa-arrow-trend-up mr-1"></i>Aktivitas Mingguan</span>
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">Grafik volume penanganan tiket dalam 7 hari terakhir.</p>

                <div class="relative h-56">
                    <canvas id="ticketTrendChart"
                            data-labels='@json($trendLabels)'
                            data-created='@json($trendCreated)'
                            data-closed='@json($trendClosed)'></canvas>
                </div>
            </div>

            <!-- Quick Insight Footer -->
            <div class="flex items-center justify-between pt-4 mt-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>Penyelesaian Tiket Cepat</span>
                </div>
                <span class="font-medium text-slate-700 dark:text-slate-300">Sinkronisasi Realtime Terhubung</span>
            </div>
        </div>
    </div>

    <!-- Filters Section (Figma Reference) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm transition-colors">
        <form action="{{ route('dashboard') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
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
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending (Baru)</option>
                    <option value="process" {{ request('status') === 'process' ? 'selected' : '' }}>Dalam Proses</option>
                    <option value="close" {{ request('status') === 'close' ? 'selected' : '' }}>Close (Selesai)</option>
                </select>
            </div>

            @if($user->role !== 'technician')
            <!-- Billing Instance Filter -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1.5">Aplikasi Billing Gayuh</label>
                <select name="billing_instance_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 min-h-[40px] transition-all">
                    <option value="">Semua Billing (60)</option>
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
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all text-center">Reset</a>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all text-center">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- Tickets Directory Table (Figma Reference) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-colors">
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm sm:text-base">
                    <i class="fa-solid fa-list-check text-emerald-600 dark:text-emerald-400"></i>
                    <span>Direktori Tiket Gangguan</span>
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
            </div>
        </div>

        <!-- Table Responsive -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 dark:bg-slate-800/50 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                        <th class="py-3.5 px-4">No. Tiket</th>
                        <th class="py-3.5 px-4">Pelanggan</th>
                        <th class="py-3.5 px-4">Billing Gayuh</th>
                        <th class="py-3.5 px-4">Kategori & Masalah</th>
                        <th class="py-3.5 px-4">Teknisi</th>
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- No. Tiket -->
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>

                            <!-- Pelanggan -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $ticket->customer_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">ID: {{ $ticket->no_services }}</div>
                            </td>

                            <!-- Billing Gayuh -->
                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $ticket->billingInstance->tenant_code ?? 'BILL' }}
                                </span>
                            </td>

                            <!-- Kategori & Masalah -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $ticket->category_name ?? 'Umum' }}</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ $ticket->problem_description }}</div>
                            </td>

                            <!-- Teknisi -->
                            <td class="py-3.5 px-4">
                                @if($ticket->assignedTechnician)
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 text-[10px] font-bold flex items-center justify-center">
                                            {{ strtoupper(substr($ticket->assignedTechnician->name, 0, 1)) }}
                                        </div>
                                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $ticket->assignedTechnician->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum ditugaskan</span>
                                @endif
                            </td>

                            <!-- Tanggal -->
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-[11px]">
                                {{ $ticket->created_at->format('d M Y, H:i') }}
                            </td>

                            <!-- Status Badge (Figma Soft Badges) -->
                            <td class="py-3.5 px-4">
                                @if($ticket->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60 uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Pending
                                    </span>
                                @elseif($ticket->status === 'process')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60 uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Proses
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 uppercase">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('tickets.show', $ticket->id) }}" 
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 rounded-xl text-xs font-semibold transition-all">
                                    <span>Detail</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 dark:text-slate-600 block"></i>
                                <span class="font-medium text-sm">Tidak ada tiket yang ditemukan dengan filter saat ini.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($tickets->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Tiket (Figma Reference) -->
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

        <form action="{{ route('tickets.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Pilih Pelanggan (Searchable) -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400">Pilih Dari Database Pelanggan</label>
                    <span id="customerCountBadge" class="text-[10px] text-emerald-600 font-semibold">{{ count($customersList) }} Pelanggan</span>
                </div>
                <div class="relative mb-2">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fa-solid fa-magnifying-glass text-xs"></i></span>
                    <input type="text" id="customerSearchBox" placeholder="Ketik nama atau ID pelanggan..." oninput="filterCustomerSelectOptions(this.value)"
                           class="w-full pl-8 pr-8 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    <button type="button" id="clearCustomerSearchBtn" onclick="clearCustomerSearchBox()" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <select id="selectCustomerBilling" onchange="autoFillCustomerData(this)"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    <option value="">-- Pilih Pelanggan Tersimpan --</option>
                    @foreach($customersList as $cust)
                        <option value="{{ $cust->no_services }}"
                                data-name="{{ $cust->customer_name }}"
                                data-phone="{{ $cust->customer_phone }}"
                                data-address="{{ $cust->customer_address }}"
                                data-billing="{{ $cust->billing_instance_id }}">
                            {{ $cust->no_services }} - {{ $cust->customer_name }}
                        </option>
                    @endforeach
                </select>
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
                    Simpan & Sinkronkan
                </button>
            </div>
        </form>
    </div>
</div>

<script id="customers-data" type="application/json">
@json($customersList)
</script>

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

    customerSelect.innerHTML = '<option value="">-- Pilih Pelanggan Tersimpan --</option>';
    filtered.forEach(cust => {
        const opt = document.createElement('option');
        opt.value = cust.no_services;
        opt.setAttribute('data-name', cust.customer_name || '');
        opt.setAttribute('data-phone', cust.customer_phone || '');
        opt.setAttribute('data-address', cust.customer_address || '');
        opt.setAttribute('data-billing', cust.billing_instance_id || '');
        opt.textContent = `${cust.no_services} - ${cust.customer_name}`;
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
    if (!billingId) billingId = 'all';

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
            if (customerSelect) customerSelect.disabled = false;
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

<script>
// Chart.js Visualizations (Figma Reference)
document.addEventListener('DOMContentLoaded', function() {
    // 1. Donut Chart: Ticket Volumes
    const donutCtx = document.getElementById('ticketVolumesChart');
    if (donutCtx && typeof Chart !== 'undefined') {
        const isDark = document.documentElement.classList.contains('dark');
        const pending = parseInt(donutCtx.dataset.pending || '0', 10);
        const process = parseInt(donutCtx.dataset.process || '0', 10);
        const close = parseInt(donutCtx.dataset.close || '0', 10);
        const total = pending + process + close;

        const chartData = total > 0 ? [pending, process, close] : [1];
        const chartLabels = total > 0 ? ['Pending', 'Proses', 'Selesai (Close)'] : ['Belum Ada Tiket'];
        const chartColors = total > 0 ? ['#0284C7', '#EA580C', '#10B981'] : ['#94A3B8'];

        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: 2,
                    borderColor: isDark ? '#0f172a' : '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (total === 0) return ' Belum ada data tiket';
                                const val = context.raw || 0;
                                const pct = ((val / total) * 100).toFixed(1);
                                return ' ' + context.label + ': ' + val + ' tiket (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Bar Chart: Weekly Activity Trend (Real Database Data)
    const trendCtx = document.getElementById('ticketTrendChart');
    if (trendCtx && typeof Chart !== 'undefined') {
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        let labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        let createdData = [0, 0, 0, 0, 0, 0, 0];
        let closedData = [0, 0, 0, 0, 0, 0, 0];

        try {
            if (trendCtx.dataset.labels) labels = JSON.parse(trendCtx.dataset.labels);
            if (trendCtx.dataset.created) createdData = JSON.parse(trendCtx.dataset.created);
            if (trendCtx.dataset.closed) closedData = JSON.parse(trendCtx.dataset.closed);
        } catch (e) {
            console.error('Error parsing trend chart data:', e);
        }

        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Tiket Selesai',
                        data: closedData,
                        backgroundColor: '#10B981',
                        borderRadius: 6
                    },
                    {
                        label: 'Tiket Baru',
                        data: createdData,
                        backgroundColor: '#38BDF8',
                        hoverBackgroundColor: '#0284C7',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            font: { size: 10 },
                            stepSize: 1,
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            color: textColor,
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ' + context.raw + ' tiket';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
