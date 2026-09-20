@extends('layouts.app')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/80 dark:bg-slate-900/60 p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-white/10 shadow-sm dark:shadow-xl relative overflow-hidden backdrop-blur-sm transition-colors">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/20 dark:via-white/20 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 border border-white/20 flex-shrink-0">
                    <i class="fa-solid fa-users-gear text-base sm:text-lg"></i>
                </div>
                <span>Direktori Terpusat Pelanggan</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Pusat data pelanggan tersinkronisasi dari 60 server billing CodeIgniter 3 mitra.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <form action="{{ route('customers.syncBilling') }}" method="POST" class="flex-1 sm:flex-none">
                @csrf
                <input type="hidden" name="tenant_code" value="BILL-001">
                <button type="submit" 
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/25 border border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[44px]">
                    <i class="fa-solid fa-rotate"></i> Sync Bill-GYH
                </button>
            </form>
            <a href="{{ route('dashboard') }}" 
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl border border-slate-300 dark:border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[44px]">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Stat Cards (2 cols mobile, 3 cols tablet, 5 cols desktop) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-4">
        <!-- Total Pelanggan -->
        <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 p-3.5 sm:p-4 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group hover:border-slate-300 dark:hover:border-white/20 transition-all">
            <div class="absolute -right-3 -bottom-3 text-slate-300/40 dark:text-slate-700/20 text-4xl sm:text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-users"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 tracking-wider">Total</div>
            <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($totalCustomers) }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Tersinkron</div>
        </div>

        <!-- Pelanggan Active -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'active'])) }}"
           class="bg-white/80 dark:bg-slate-900/50 border {{ request('status') === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-emerald-200 dark:border-emerald-500/30' }} hover:border-emerald-400 dark:hover:border-emerald-500/60 p-3.5 sm:p-4 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400">
            <div class="absolute -right-3 -bottom-3 text-emerald-500/10 text-4xl sm:text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-circle-check"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300 tracking-wider">Aktif</div>
            <div class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($activeCustomers) }}</div>
            <div class="text-[10px] text-emerald-600/90 dark:text-emerald-400/90 mt-0.5 truncate">Layanan aktif</div>
        </a>

        <!-- Pelanggan Isolated -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'isolated'])) }}"
           class="bg-white/80 dark:bg-slate-900/50 border {{ request('status') === 'isolated' ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-amber-200 dark:border-amber-500/30' }} hover:border-amber-400 dark:hover:border-amber-500/60 p-3.5 sm:p-4 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-amber-400">
            <div class="absolute -right-3 -bottom-3 text-amber-500/10 text-4xl sm:text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-amber-700 dark:text-amber-300 tracking-wider">Isolir</div>
            <div class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($isolatedCustomers) }}</div>
            <div class="text-[10px] text-amber-600/90 dark:text-amber-400/90 mt-0.5 truncate">Terisolir</div>
        </a>

        <!-- Pelanggan Inactive -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'inactive'])) }}"
           class="bg-white/80 dark:bg-slate-900/50 border {{ request('status') === 'inactive' ? 'border-rose-500 ring-2 ring-rose-500/30' : 'border-rose-200 dark:border-rose-500/30' }} hover:border-rose-400 dark:hover:border-rose-500/60 p-3.5 sm:p-4 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-rose-400">
            <div class="absolute -right-3 -bottom-3 text-rose-500/10 text-4xl sm:text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-user-xmark"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-rose-700 dark:text-rose-300 tracking-wider">Non-Aktif</div>
            <div class="text-xl sm:text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ number_format($inactiveCustomers) }}</div>
            <div class="text-[10px] text-rose-600/90 dark:text-rose-400/90 mt-0.5 truncate">Berhenti langganan</div>
        </a>

        <!-- Pelanggan Free -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'free'])) }}"
           class="col-span-2 sm:col-span-1 bg-white/80 dark:bg-slate-900/50 border {{ request('status') === 'free' ? 'border-sky-500 ring-2 ring-sky-500/30' : 'border-sky-200 dark:border-sky-500/30' }} hover:border-sky-400 dark:hover:border-sky-500/60 p-3.5 sm:p-4 rounded-2xl shadow-sm dark:shadow-lg relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-sky-400">
            <div class="absolute -right-3 -bottom-3 text-sky-500/10 text-4xl sm:text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-gift"></i></div>
            <div class="text-[10px] sm:text-xs font-semibold uppercase text-sky-700 dark:text-sky-300 tracking-wider">Free</div>
            <div class="text-xl sm:text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ number_format($freeCustomers) }}</div>
            <div class="text-[10px] text-sky-600/90 dark:text-sky-400/90 mt-0.5 truncate">Gratis / Promo</div>
        </a>
    </div>

    <!-- Global Search & Filters Section -->
    <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 p-4 sm:p-5 rounded-2xl shadow-sm dark:shadow-lg space-y-3 sm:space-y-4 relative overflow-hidden transition-colors">
        <form action="{{ route('customers.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Global Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase text-blue-700 dark:text-blue-300 mb-1.5">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Global Search (Instan 60 Billing)
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari no layanan, nama, phone, ODP..." 
                           class="w-full pl-9 pr-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                </div>
            </div>

            <!-- Billing Node Origin Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-server mr-1"></i> Asal Server Billing
                </label>
                <select name="billing_node_id" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">Semua Server (60 Nodes)</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('billing_node_id') == $t->id ? 'selected' : '' }}>
                            [{{ $t->tenant_code }}] {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Subscription Status Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-signal mr-1"></i> Status Langganan
                </label>
                <select name="status" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                    <option value="isolated" {{ request('status') === 'isolated' ? 'selected' : '' }}>Isolated (Isolir)</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive (Non-Aktif)</option>
                    <option value="free" {{ request('status') === 'free' ? 'selected' : '' }}>Free (Gratis)</option>
                </select>
            </div>

            <!-- ODP Filter (Optional) -->
            @if(isset($odpList) && count($odpList) > 0)
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-sitemap mr-1"></i> Filter ODP
                </label>
                <select name="odp_name" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                    <option value="">Semua Titik ODP</option>
                    @foreach($odpList as $odp)
                        <option value="{{ $odp }}" {{ request('odp_name') === $odp ? 'selected' : '' }}>
                            {{ $odp }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-span-full flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 pt-3 border-t border-slate-200 dark:border-white/5">
                <a href="{{ route('customers.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl border border-slate-300 dark:border-white/10 transition-all text-center focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[44px] flex items-center justify-center">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filter
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow-lg shadow-blue-600/25 border border-white/10 transition-all text-center focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px] flex items-center justify-center">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari Data Pelanggan
                </button>
            </div>
        </form>
    </div>

    <!-- Customer Directory Container -->
    <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 rounded-2xl shadow-sm dark:shadow-xl overflow-hidden transition-colors">
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-white/10 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm sm:text-base">
                <i class="fa-solid fa-table-list text-blue-600 dark:text-blue-400"></i>
                <span>Direktori Pelanggan Terpusat</span>
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400">Menampilkan {{ $customers->count() }} dari {{ $customers->total() }} pelanggan</span>
        </div>

        <!-- Desktop Table View (Visible on md screens and up) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-950/60 text-slate-600 dark:text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-200/80 dark:border-white/10">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12">No</th>
                        <th class="px-4 py-3.5">Identitas Billing Origin</th>
                        <th class="px-4 py-3.5">No Layanan</th>
                        <th class="px-4 py-3.5">Nama Pelanggan</th>
                        <th class="px-4 py-3.5">Kontak & Alamat</th>
                        <th class="px-4 py-3.5">Paket & Tagihan</th>
                        <th class="px-4 py-3.5">Titik ODP</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Quick Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-white/5">
                    @forelse($customers as $index => $customer)
                        @php
                            $billing = $customer->billingNode ?? ($billingMap[$customer->billing_instance_id] ?? null);
                            $phoneClean = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : null;
                            if ($phoneClean && str_starts_with($phoneClean, '0')) {
                                $phoneClean = '62' . substr($phoneClean, 1);
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-4 py-4 text-center font-medium text-slate-500 dark:text-slate-400">
                                {{ $customers->firstItem() + $index }}.
                            </td>
                            <!-- Billing Node Identity Badge -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($billing)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30">
                                        <i class="fa-solid fa-server text-[10px]"></i>
                                        <span>[{{ $billing->tenant_code }}] {{ $billing->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                @endif
                            </td>
                            <!-- No Layanan -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-xs">{{ $customer->no_services }}</span>
                            </td>
                            <!-- Nama Pelanggan -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-blue-600/15 border border-blue-400/30 text-blue-600 dark:text-blue-300 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($customer->name ?? $customer->customer_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-slate-200">{{ $customer->name ?? $customer->customer_name }}</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">ID Remote: #{{ $customer->remote_customer_id ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <!-- Kontak & Alamat -->
                            <td class="px-4 py-4 max-w-xs">
                                <div class="text-slate-800 dark:text-slate-200 font-medium">{{ $customer->phone ?? $customer->customer_phone ?? '-' }}</div>
                                <p class="line-clamp-1 text-slate-500 dark:text-slate-400 text-[11px] mt-0.5">{{ $customer->address ?? $customer->customer_address ?? '-' }}</p>
                            </td>
                            <!-- Paket & Tagihan -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-semibold text-indigo-700 dark:text-indigo-300">{{ $customer->package_name ?? 'Regular' }}</div>
                                @if($customer->monthly_fee)
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}/bln</div>
                                @endif
                            </td>
                            <!-- Titik ODP -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($customer->odp_name)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-mono text-cyan-700 dark:text-cyan-300 bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/30 px-2 py-0.5 rounded">
                                        <i class="fa-solid fa-sitemap text-[9px]"></i> {{ $customer->odp_name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                @endif
                            </td>
                            <!-- Status Badge -->
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                @php $custStatus = strtolower($customer->status ?? 'active'); @endphp
                                @if($custStatus === 'active')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 uppercase tracking-wider">Active</span>
                                @elseif($custStatus === 'isolated')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 uppercase tracking-wider">Isolated</span>
                                @elseif($custStatus === 'free')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-500/20 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-500/30 uppercase tracking-wider">Free</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30 uppercase tracking-wider">Inactive</span>
                                @endif
                            </td>
                            <!-- Quick Actions -->
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- 1-Click WhatsApp -->
                                    @if($phoneClean)
                                        <a href="https://wa.me/{{ $phoneClean }}" target="_blank"
                                           class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white dark:bg-emerald-600/20 dark:hover:bg-emerald-600 dark:text-emerald-300 dark:hover:text-white rounded-lg border border-emerald-200 dark:border-emerald-500/30 transition-all text-[11px] font-medium flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[36px]"
                                           title="1-Click WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i> WA
                                        </a>
                                    @endif

                                    <!-- 1-Click Maps -->
                                    @if($customer->latitude && $customer->longitude)
                                        <a href="https://www.google.com/maps/place/{{ $customer->latitude }},{{ $customer->longitude }}" target="_blank"
                                           class="px-2.5 py-1.5 bg-cyan-50 hover:bg-cyan-600 text-cyan-700 hover:text-white dark:bg-cyan-600/20 dark:hover:bg-cyan-600 dark:text-cyan-300 dark:hover:text-white rounded-lg border border-cyan-200 dark:border-cyan-500/30 transition-all text-[11px] font-medium flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-cyan-400 min-h-[36px]"
                                           title="1-Click Google Maps">
                                            <i class="fa-solid fa-map-location-dot"></i> Maps
                                        </a>
                                    @endif

                                    <!-- Buat Tiket Gangguan -->
                                    <button type="button" 
                                            onclick="openCreateTicketForCustomer('{{ $customer->no_services }}', '{{ addslashes($customer->name ?? $customer->customer_name) }}', '{{ $customer->phone ?? $customer->customer_phone }}', '{{ addslashes($customer->address ?? $customer->customer_address) }}', '{{ $customer->billing_node_id ?? $customer->billing_instance_id }}')"
                                            class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white dark:bg-blue-600/20 dark:hover:bg-blue-600 dark:text-blue-300 dark:hover:text-white rounded-lg border border-blue-200 dark:border-blue-400/30 transition-all text-[11px] font-medium flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[36px]"
                                            title="Buat Tiket Gangguan">
                                        <i class="fa-solid fa-ticket"></i> Tiket
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                <i class="fa-solid fa-user-slash text-4xl mb-3 block text-slate-400 dark:text-slate-600"></i>
                                @if(request('search'))
                                    Tidak ditemukan pelanggan dengan kata kunci "<strong class="text-slate-700 dark:text-slate-300">{{ request('search') }}</strong>".
                                @else
                                    Belum ada data pelanggan yang tersinkronisasi dari server billing.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Touch Card List (Visible on mobile screens < md, antislop R-03) -->
        <div class="block md:hidden divide-y divide-slate-200 dark:divide-white/5">
            @forelse($customers as $index => $customer)
                @php
                    $billing = $customer->billingNode ?? ($billingMap[$customer->billing_instance_id] ?? null);
                    $phoneClean = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : null;
                    if ($phoneClean && str_starts_with($phoneClean, '0')) {
                        $phoneClean = '62' . substr($phoneClean, 1);
                    }
                    $custStatus = strtolower($customer->status ?? 'active');
                @endphp
                <div class="p-4 space-y-3 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    <!-- Top Row: Service Number & Status Badge -->
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400">{{ $customer->no_services }}</span>
                        <div>
                            @if($custStatus === 'active')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 uppercase tracking-wider">Active</span>
                            @elseif($custStatus === 'isolated')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 uppercase tracking-wider">Isolated</span>
                            @elseif($custStatus === 'free')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-500/20 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-500/30 uppercase tracking-wider">Free</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30 uppercase tracking-wider">Inactive</span>
                            @endif
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-full bg-blue-600/15 border border-blue-400/30 text-blue-600 dark:text-blue-300 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                            {{ strtoupper(substr($customer->name ?? $customer->customer_name ?? 'C', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ $customer->name ?? $customer->customer_name }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $customer->phone ?? $customer->customer_phone ?? '-' }}</div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $customer->address ?? $customer->customer_address ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Metadata Tags (Billing, Package, ODP) -->
                    <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                        @if($billing)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:border-indigo-500/30 font-medium">
                                <i class="fa-solid fa-server text-[9px]"></i> {{ $billing->name }}
                            </span>
                        @endif
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                            {{ $customer->package_name ?? 'Regular' }}
                        </span>
                        @if($customer->odp_name)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-300 dark:border-cyan-500/30 font-mono">
                                <i class="fa-solid fa-sitemap text-[9px]"></i> {{ $customer->odp_name }}
                            </span>
                        @endif
                    </div>

                    <!-- Action Buttons (44x44px touch targets) -->
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        @if($phoneClean)
                            <a href="https://wa.me/{{ $phoneClean }}" target="_blank"
                               class="flex items-center justify-center gap-1.5 px-3 py-2.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white dark:bg-emerald-600/20 dark:hover:bg-emerald-600 dark:text-emerald-300 dark:hover:text-white rounded-xl border border-emerald-200 dark:border-emerald-500/30 text-xs font-semibold transition-all min-h-[44px]">
                                <i class="fa-brands fa-whatsapp text-sm"></i> WA
                            </a>
                        @else
                            <button disabled class="opacity-40 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl text-xs min-h-[44px]">
                                <i class="fa-brands fa-whatsapp text-sm"></i> WA
                            </button>
                        @endif

                        @if($customer->latitude && $customer->longitude)
                            <a href="https://www.google.com/maps/place/{{ $customer->latitude }},{{ $customer->longitude }}" target="_blank"
                               class="flex items-center justify-center gap-1.5 px-3 py-2.5 bg-cyan-50 hover:bg-cyan-600 text-cyan-700 hover:text-white dark:bg-cyan-600/20 dark:hover:bg-cyan-600 dark:text-cyan-300 dark:hover:text-white rounded-xl border border-cyan-200 dark:border-cyan-500/30 text-xs font-semibold transition-all min-h-[44px]">
                                <i class="fa-solid fa-map-location-dot text-sm"></i> Maps
                            </a>
                        @else
                            <button disabled class="opacity-40 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl text-xs min-h-[44px]">
                                <i class="fa-solid fa-map-location-dot text-sm"></i> Maps
                            </button>
                        @endif

                        <button type="button" 
                                onclick="openCreateTicketForCustomer('{{ $customer->no_services }}', '{{ addslashes($customer->name ?? $customer->customer_name) }}', '{{ $customer->phone ?? $customer->customer_phone }}', '{{ addslashes($customer->address ?? $customer->customer_address) }}', '{{ $customer->billing_node_id ?? $customer->billing_instance_id }}')"
                                class="flex items-center justify-center gap-1.5 px-3 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold shadow-sm transition-all min-h-[44px]">
                            <i class="fa-solid fa-ticket text-sm"></i> Tiket
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                    <i class="fa-solid fa-user-slash text-3xl mb-2 block text-slate-400 dark:text-slate-600"></i>
                    Belum ada data pelanggan yang cocok.
                </div>
            @endforelse
        </div>

        @if($customers->hasPages())
            <div class="p-3 sm:p-4 border-t border-slate-200/80 dark:border-white/10 bg-slate-50/50 dark:bg-slate-950/60">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Quick Ticket Creation from Customer List (Responsive dialog) -->
<div id="quickTicketModal" class="hidden fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-md" onclick="document.getElementById('quickTicketModal').classList.add('hidden')"></div>
    <div class="relative bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-white/15 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-xl max-h-[92vh] overflow-y-auto backdrop-blur-xl animate-modal transition-colors z-10">
        <div class="sticky top-0 bg-white/95 dark:bg-slate-900/95 border-b border-slate-200 dark:border-white/10 p-4 sm:p-5 rounded-t-3xl flex items-center justify-between z-10 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white border border-white/20 shadow-lg shadow-blue-500/25">
                    <i class="fa-solid fa-ticket text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Buat Tiket Gangguan Instan</h2>
                    <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Otomatis terisi data pelanggan yang dipilih</p>
                </div>
            </div>
            <button onclick="document.getElementById('quickTicketModal').classList.add('hidden')" 
                    class="w-10 h-10 sm:w-8 sm:h-8 rounded-xl sm:rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800/70 dark:hover:bg-slate-700 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all border border-slate-200 dark:border-white/10 focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[44px] sm:min-h-0"
                    aria-label="Tutup modal">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf
            <input type="hidden" name="billing_instance_id" id="qt_billing_id">

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Nama Pelanggan</label>
                <input type="text" name="customer_name" id="qt_customer_name" required readonly class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white min-h-[44px]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">No Layanan</label>
                    <input type="text" name="no_services" id="qt_no_services" required readonly class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-blue-600 dark:text-blue-400 font-mono min-h-[44px]">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">No Telp</label>
                    <input type="text" name="customer_phone" id="qt_customer_phone" class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white min-h-[44px]">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Alamat</label>
                <textarea name="customer_address" id="qt_customer_address" rows="2" class="w-full px-3 py-2.5 bg-slate-100 dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Kategori Gangguan</label>
                <input type="text" name="category_name" placeholder="Contoh: FO Putus, Modem Red, Internet Lelet" class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Deskripsi Masalah *</label>
                <textarea name="problem_description" rows="3" required placeholder="Detail laporan masalah pelanggan..." class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30"></textarea>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 pt-3 sm:pt-4 border-t border-slate-200 dark:border-white/10">
                <button type="button" onclick="document.getElementById('quickTicketModal').classList.add('hidden')" class="px-5 py-3 sm:py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs border border-slate-300 dark:border-white/10 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[44px] flex items-center justify-center">Batal</button>
                <button type="submit" class="px-6 py-3 sm:py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-blue-600/25 border border-white/10 flex items-center justify-center gap-1.5 transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px]"><i class="fa-solid fa-paper-plane"></i> Buat Tiket</button>
            </div>
        </form>
    </div>
</div>

<script>
// Close quickTicketModal with Escape key (antislop R-32 keyboard accessibility)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('quickTicketModal');
        if (modal && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }
    }
});

function openCreateTicketForCustomer(noServices, name, phone, address, billingId) {
    document.getElementById('qt_no_services').value = noServices || '';
    document.getElementById('qt_customer_name').value = name || '';
    document.getElementById('qt_customer_phone').value = phone || '';
    document.getElementById('qt_customer_address').value = address || '';
    document.getElementById('qt_billing_id').value = billingId || '1';

    document.getElementById('quickTicketModal').classList.remove('hidden');
}
</script>
@endsection
