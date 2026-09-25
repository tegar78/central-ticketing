@extends('layouts.app')

@section('title', 'Dashboard Monitoring')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                    @if($user->role === 'technician')
                    Portal Teknisi
                    @else
                    Portal Ticketing
                    @endif
                </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Dashboard Dukungan Tiket
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                @if($user->role === 'technician')
                Menampilkan performa penanganan tiket yang ditugaskan kepada Anda ({{ $user->name }}).
                @else
                Monitoring, tren penanganan gangguan, dan rekam jejak aktivitas operasional seluruh billing Gayuh.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
            <a href="{{ route('tickets.index') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm rounded-xl transition-all border border-slate-200/60 dark:border-slate-700">
                <i class="fa-solid fa-list-check text-xs text-emerald-600 dark:text-emerald-400"></i>
                <span>Direktori Semua Tiket</span>
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

    <!-- 4 KPI Stat Cards (Clickable Direct to Ticket Directory) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        <!-- Card 1: Total Tiket (Navigates to /tickets) -->
        <a href="{{ route('tickets.index') }}"
            title="Klik untuk membuka Direktori Seluruh Tiket"
            class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500 hover:ring-2 hover:ring-emerald-500/20 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group transition-all block cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-slate-400 dark:text-slate-500 tracking-wider group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Total Tiket</span>
                <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-sm group-hover:scale-110 group-hover:bg-emerald-50 group-hover:text-emerald-600 dark:group-hover:bg-emerald-950/40 dark:group-hover:text-emerald-400 transition-all">
                    <i class="fa-solid fa-ticket"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2 sm:mt-3">{{ number_format($totalCount) }}</div>
            <div class="flex items-center justify-between mt-2 text-xs">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <i class="fa-solid fa-arrow-trend-up text-[8px]"></i> 100%
                </span>
                <span class="text-emerald-600 dark:text-emerald-400 text-[11px] font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                    Buka Direktori <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </span>
            </div>
        </a>

        <!-- Card 2: Pending (Tiket Baru) -->
        <a href="{{ route('tickets.index', ['status' => 'pending']) }}"
            title="Klik untuk membuka tiket Pending"
            class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-sky-400 dark:hover:border-sky-600 hover:ring-2 hover:ring-sky-500/20 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group transition-all block cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-sky-600 dark:text-sky-400 tracking-wider">Pending</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-sky-600 dark:text-sky-400 mt-2 sm:mt-3">{{ number_format($pendingCount) }}</div>
            <div class="flex items-center justify-between mt-2 text-xs">
                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                    Menunggu
                </span>
                <span class="text-sky-600 dark:text-sky-400 text-[11px] font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                    Lihat <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </span>
            </div>
        </a>

        <!-- Card 3: Process -->
        <a href="{{ route('tickets.index', ['status' => 'process']) }}"
            title="Klik untuk membuka tiket Dalam Proses"
            class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-amber-400 dark:hover:border-amber-600 hover:ring-2 hover:ring-amber-500/20 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group transition-all block cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-amber-600 dark:text-amber-400 tracking-wider">Dalam Proses</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-spinner"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2 sm:mt-3">{{ number_format($processCount) }}</div>
            <div class="flex items-center justify-between mt-2 text-xs">
                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                    Pengerjaan
                </span>
                <span class="text-amber-600 dark:text-amber-400 text-[11px] font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                    Lihat <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </span>
            </div>
        </a>

        <!-- Card 4: Selesai (Close) -->
        <a href="{{ route('tickets.index', ['status' => 'close']) }}"
            title="Klik untuk membuka tiket Selesai"
            class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-400 dark:hover:border-emerald-600 hover:ring-2 hover:ring-emerald-500/20 p-4 sm:p-5 rounded-2xl shadow-sm relative overflow-hidden group transition-all block cursor-pointer">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase text-emerald-600 dark:text-emerald-400 tracking-wider">Selesai</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2 sm:mt-3">{{ number_format($closeCount) }}</div>
            <div class="flex items-center justify-between mt-2 text-xs">
                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    Terselesaikan
                </span>
                <span class="text-emerald-600 dark:text-emerald-400 text-[11px] font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                    Lihat <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </span>
            </div>
        </a>
    </div>

    <!-- Visual Analytics Section -->
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

                <!-- Selected Customer Card (Shows active selection with reset button) -->
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
                    <!-- Dynamic Live Search List Items -->
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
                    Simpan & Sinkronkan
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

        // Auto fill form inputs
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

        // Update selected customer banner
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

        // Update search box display and hide dropdown
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

        // Clear ticket form inputs
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

    // Close live search dropdown when clicking outside
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
                    datasets: [{
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
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 10
                                },
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
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
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