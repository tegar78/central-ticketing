@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden transition-colors">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 flex-shrink-0">
                    <i class="fa-solid fa-users-gear text-base"></i>
                </div>
                <span>Direktori Terpusat Pelanggan</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Pusat data pelanggan tersinkronisasi dari server billing CodeIgniter 3 Gayuh.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <form action="{{ route('customers.syncBilling') }}" method="POST" class="flex flex-wrap sm:flex-nowrap items-center gap-2 flex-1 sm:flex-none">
                @csrf
                <select name="tenant_code" class="px-3 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 min-h-[42px]">
                    <option value="all">Semua Server Billing</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->tenant_code }}" {{ $t->tenant_code === 'BILL-001' ? 'selected' : '' }}>
                            [{{ $t->tenant_code }}] {{ $t->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" 
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-sm shadow-emerald-600/20 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[42px]">
                    <i class="fa-solid fa-rotate"></i> Sync Data
                </button>
            </form>
            <a href="{{ route('dashboard') }}" 
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[42px]">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Stat Cards (2 cols mobile, 3 cols tablet, 5 cols desktop) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- Total Pelanggan -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 rounded-2xl shadow-sm relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all">
            <div class="absolute -right-3 -bottom-3 text-slate-200/50 dark:text-slate-800/40 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-users"></i></div>
            <div class="text-[11px] font-semibold uppercase text-slate-400 dark:text-slate-500 tracking-wider">Total</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($totalCustomers) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Tersinkron</div>
        </div>

        <!-- Pelanggan Active -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'active'])) }}"
           class="bg-white dark:bg-slate-900 border {{ request('status') === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-emerald-400 dark:hover:border-emerald-500/60 p-4 rounded-2xl shadow-sm relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400">
            <div class="absolute -right-3 -bottom-3 text-emerald-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-circle-check"></i></div>
            <div class="text-[11px] font-semibold uppercase text-emerald-600 dark:text-emerald-400 tracking-wider">Aktif</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($activeCustomers) }}</div>
            <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">Layanan aktif</div>
        </a>

        <!-- Pelanggan Isolated -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'isolated'])) }}"
           class="bg-white dark:bg-slate-900 border {{ request('status') === 'isolated' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-amber-400 dark:hover:border-amber-500/60 p-4 rounded-2xl shadow-sm relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-amber-400">
            <div class="absolute -right-3 -bottom-3 text-amber-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="text-[11px] font-semibold uppercase text-amber-600 dark:text-amber-400 tracking-wider">Isolir</div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($isolatedCustomers) }}</div>
            <div class="text-[11px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 truncate">Terisolir</div>
        </a>

        <!-- Pelanggan Inactive -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'inactive'])) }}"
           class="bg-white dark:bg-slate-900 border {{ request('status') === 'inactive' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-rose-400 dark:hover:border-rose-500/60 p-4 rounded-2xl shadow-sm relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-rose-400">
            <div class="absolute -right-3 -bottom-3 text-rose-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-user-xmark"></i></div>
            <div class="text-[11px] font-semibold uppercase text-rose-600 dark:text-rose-400 tracking-wider">Non-Aktif</div>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ number_format($inactiveCustomers) }}</div>
            <div class="text-[11px] text-rose-600/80 dark:text-rose-400/80 mt-0.5 truncate">Berhenti langganan</div>
        </a>

        <!-- Pelanggan Free -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'free'])) }}"
           class="col-span-2 sm:col-span-1 bg-white dark:bg-slate-900 border {{ request('status') === 'free' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'border-slate-200/80 dark:border-slate-800' }} hover:border-sky-400 dark:hover:border-sky-500/60 p-4 rounded-2xl shadow-sm relative overflow-hidden group transition-all focus:outline-none focus:ring-2 focus:ring-sky-400">
            <div class="absolute -right-3 -bottom-3 text-sky-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-gift"></i></div>
            <div class="text-[11px] font-semibold uppercase text-sky-600 dark:text-sky-400 tracking-wider">Free</div>
            <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ number_format($freeCustomers) }}</div>
            <div class="text-[11px] text-sky-600/80 dark:text-sky-400/80 mt-0.5 truncate">Gratis / Promo</div>
        </a>
    </div>

    <!-- Global Search & Filters Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm space-y-4 relative overflow-hidden transition-colors">
        <form action="{{ route('customers.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Global Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-magnifying-glass text-emerald-500 mr-1"></i> Global Search (60 Billing)
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-solid fa-magnifying-glass text-xs"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari no layanan, nama, phone, ODP..." 
                           class="w-full pl-9 pr-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 min-h-[42px]">
                </div>
            </div>

            <!-- Billing Node Origin Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                    <i class="fa-solid fa-server text-emerald-500 mr-1"></i> Asal Server Billing
                </label>
                <select name="billing_node_id" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 min-h-[42px]">
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
                    <i class="fa-solid fa-signal text-emerald-500 mr-1"></i> Status Langganan
                </label>
                <select name="status" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 min-h-[42px]">
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
                    <i class="fa-solid fa-sitemap text-emerald-500 mr-1"></i> Filter ODP
                </label>
                <select name="odp_name" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 min-h-[42px]">
                    <option value="">Semua Titik ODP</option>
                    @foreach($odpList as $odp)
                        <option value="{{ $odp }}" {{ request('odp_name') === $odp ? 'selected' : '' }}>
                            {{ $odp }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-span-full flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 pt-3 border-t border-slate-200/80 dark:border-slate-800">
                <a href="{{ route('customers.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 transition-all text-center focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[42px] flex items-center justify-center">
                    <i class="fa-solid fa-rotate-left mr-1.5"></i> Reset Filter
                </a>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-sm shadow-emerald-600/20 transition-all text-center focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[42px] flex items-center justify-center">
                    <i class="fa-solid fa-magnifying-glass mr-1.5"></i> Cari Pelanggan
                </button>
            </div>
        </form>
    </div>

    <!-- Customer Directory Container -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-colors">
        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm sm:text-base">
                    <i class="fa-solid fa-table-list text-emerald-500"></i>
                    <span>Direktori Pelanggan</span>
                </h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">Menampilkan {{ $customers->count() }} dari {{ $customers->total() }} pelanggan</span>
            </div>

            <!-- Quick Sorting Controls -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider hidden sm:inline">Urutan:</span>
                
                <!-- Sort No Layanan Group -->
                <div class="inline-flex items-center rounded-xl bg-slate-50 dark:bg-slate-800/80 p-1 border border-slate-200 dark:border-slate-700">
                    <span class="text-[11px] font-medium text-slate-600 dark:text-slate-300 px-2 flex items-center gap-1">
                        <i class="fa-solid fa-hashtag text-[10px] text-emerald-500"></i> Layanan:
                    </span>
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'no_services', 'sort_dir' => 'asc']) }}"
                       class="px-2.5 py-1 text-[11px] font-semibold rounded-lg transition-all flex items-center gap-1 {{ request('sort_by') === 'no_services' && request('sort_dir', 'asc') === 'asc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                       title="No Layanan Ascending (0-9)">
                        <i class="fa-solid fa-arrow-up-1-9"></i> Asc
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'no_services', 'sort_dir' => 'desc']) }}"
                       class="px-2.5 py-1 text-[11px] font-semibold rounded-lg transition-all flex items-center gap-1 {{ request('sort_by') === 'no_services' && request('sort_dir') === 'desc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                       title="No Layanan Descending (9-0)">
                        <i class="fa-solid fa-arrow-down-9-1"></i> Desc
                    </a>
                </div>

                <!-- Sort Nama Pelanggan Group -->
                <div class="inline-flex items-center rounded-xl bg-slate-50 dark:bg-slate-800/80 p-1 border border-slate-200 dark:border-slate-700">
                    <span class="text-[11px] font-medium text-slate-600 dark:text-slate-300 px-2 flex items-center gap-1">
                        <i class="fa-solid fa-user text-[10px] text-teal-500"></i> Nama:
                    </span>
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => 'asc']) }}"
                       class="px-2.5 py-1 text-[11px] font-semibold rounded-lg transition-all flex items-center gap-1 {{ request('sort_by') === 'name' && request('sort_dir', 'asc') === 'asc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                       title="Nama Ascending (A-Z)">
                        <i class="fa-solid fa-arrow-up-a-z"></i> A-Z
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => 'desc']) }}"
                       class="px-2.5 py-1 text-[11px] font-semibold rounded-lg transition-all flex items-center gap-1 {{ request('sort_by') === 'name' && request('sort_dir') === 'desc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                       title="Nama Descending (Z-A)">
                        <i class="fa-solid fa-arrow-down-z-a"></i> Z-A
                    </a>
                </div>

                @if(request('sort_by'))
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => null, 'sort_dir' => null]) }}"
                       class="px-2.5 py-1 bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/40 dark:text-slate-400 dark:hover:text-rose-300 rounded-lg text-[11px] font-medium border border-slate-200 dark:border-slate-700 transition-colors flex items-center gap-1"
                       title="Reset Urutan ke Default">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i> Reset
                    </a>
                @endif
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5 text-center w-12">No</th>
                        <th class="px-4 py-3.5">Billing Origin</th>
                        <th class="px-4 py-3.5">
                            <div class="inline-flex items-center gap-1.5">
                                <span>No Layanan</span>
                                <div class="inline-flex items-center rounded-md bg-slate-200/60 dark:bg-slate-800 p-0.5 border border-slate-300/50 dark:border-slate-700">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'no_services', 'sort_dir' => 'asc']) }}"
                                       class="w-4 h-4 flex items-center justify-center rounded {{ request('sort_by') === 'no_services' && request('sort_dir', 'asc') === 'asc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-white' }}"
                                       title="Urutkan No Layanan Ascending (0-9)">
                                        <i class="fa-solid fa-arrow-up-1-9 text-[9px]"></i>
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'no_services', 'sort_dir' => 'desc']) }}"
                                       class="w-4 h-4 flex items-center justify-center rounded {{ request('sort_by') === 'no_services' && request('sort_dir') === 'desc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-white' }}"
                                       title="Urutkan No Layanan Descending (9-0)">
                                        <i class="fa-solid fa-arrow-down-9-1 text-[9px]"></i>
                                    </a>
                                </div>
                            </div>
                        </th>
                        <th class="px-4 py-3.5">
                            <div class="inline-flex items-center gap-1.5">
                                <span>Nama Pelanggan</span>
                                <div class="inline-flex items-center rounded-md bg-slate-200/60 dark:bg-slate-800 p-0.5 border border-slate-300/50 dark:border-slate-700">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => 'asc']) }}"
                                       class="w-4 h-4 flex items-center justify-center rounded {{ request('sort_by') === 'name' && request('sort_dir', 'asc') === 'asc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-white' }}"
                                       title="Urutkan Nama Pelanggan Ascending (A-Z)">
                                        <i class="fa-solid fa-arrow-up-a-z text-[9px]"></i>
                                    </a>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => 'desc']) }}"
                                       class="w-4 h-4 flex items-center justify-center rounded {{ request('sort_by') === 'name' && request('sort_dir') === 'desc' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-white' }}"
                                       title="Urutkan Nama Pelanggan Descending (Z-A)">
                                        <i class="fa-solid fa-arrow-down-z-a text-[9px]"></i>
                                    </a>
                                </div>
                            </div>
                        </th>
                        <th class="px-4 py-3.5">Kontak & Alamat</th>
                        <th class="px-4 py-3.5">Paket & Tagihan</th>
                        <th class="px-4 py-3.5">Titik ODP</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800">
                    @forelse($customers as $index => $customer)
                        @php
                            $billing = $customer->billingNode ?? ($billingMap[$customer->billing_instance_id] ?? null);
                            $phoneClean = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : null;
                            if ($phoneClean && str_starts_with($phoneClean, '0')) {
                                $phoneClean = '62' . substr($phoneClean, 1);
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-4 py-4 text-center font-medium text-slate-400 dark:text-slate-500">
                                {{ $customers->firstItem() + $index }}.
                            </td>
                            <!-- Billing Node Identity Badge -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($billing)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40">
                                        <i class="fa-solid fa-server text-[10px]"></i>
                                        <span>[{{ $billing->tenant_code }}] {{ $billing->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                @endif
                            </td>
                            <!-- No Layanan -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs">{{ $customer->no_services }}</span>
                            </td>
                            <!-- Nama Pelanggan -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($customer->name ?? $customer->customer_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $customer->name ?? $customer->customer_name }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500">ID: #{{ $customer->remote_customer_id ?? '-' }}</div>
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
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $customer->package_name ?? 'Regular' }}</div>
                                @if($customer->monthly_fee)
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}/bln</div>
                                @endif
                            </td>
                            <!-- Titik ODP -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($customer->odp_name)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-mono text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/40 border border-teal-200/80 dark:border-teal-800/40 px-2 py-0.5 rounded-lg">
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
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 uppercase tracking-wider">Active</span>
                                @elseif($custStatus === 'isolated')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40 uppercase tracking-wider">Isolated</span>
                                @elseif($custStatus === 'free')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/40 uppercase tracking-wider">Free</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40 uppercase tracking-wider">Inactive</span>
                                @endif
                            </td>
                            <!-- Quick Actions -->
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- 1-Click WhatsApp -->
                                    @if($phoneClean)
                                        <a href="https://wa.me/{{ $phoneClean }}" target="_blank"
                                           class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white dark:bg-emerald-950/40 dark:hover:bg-emerald-600 dark:text-emerald-300 dark:hover:text-white rounded-lg border border-emerald-200/80 dark:border-emerald-800/40 transition-all text-[11px] font-medium flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[36px]"
                                           title="WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i> WA
                                        </a>
                                    @endif

                                    <!-- 1-Click Maps -->
                                    @if($customer->latitude && $customer->longitude)
                                        <a href="https://www.google.com/maps/place/{{ $customer->latitude }},{{ $customer->longitude }}" target="_blank"
                                           class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 transition-all text-[11px] font-medium flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[36px]"
                                           title="Google Maps">
                                            <i class="fa-solid fa-map-location-dot"></i> Maps
                                        </a>
                                    @endif

                                    <!-- Buat Tiket Gangguan -->
                                    <button type="button" 
                                            onclick="openCreateTicketForCustomer('{{ $customer->no_services }}', '{{ addslashes($customer->name ?? $customer->customer_name) }}', '{{ $customer->phone ?? $customer->customer_phone }}', '{{ addslashes($customer->address ?? $customer->customer_address) }}', '{{ $customer->billing_node_id ?? $customer->billing_instance_id }}')"
                                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-all text-[11px] font-medium flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[36px]"
                                            title="Buat Tiket Gangguan">
                                        <i class="fa-solid fa-plus text-[10px]"></i> Tiket
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                <i class="fa-solid fa-user-slash text-4xl mb-3 block text-slate-300 dark:text-slate-700"></i>
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

        <!-- Mobile Touch Card List -->
        <div class="block md:hidden divide-y divide-slate-200/80 dark:divide-slate-800">
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
                        <span class="font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400">{{ $customer->no_services }}</span>
                        <div>
                            @if($custStatus === 'active')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 uppercase tracking-wider">Active</span>
                            @elseif($custStatus === 'isolated')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40 uppercase tracking-wider">Isolated</span>
                            @elseif($custStatus === 'free')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/40 uppercase tracking-wider">Free</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40 uppercase tracking-wider">Inactive</span>
                            @endif
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-full bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                            {{ strtoupper(substr($customer->name ?? $customer->customer_name ?? 'C', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ $customer->name ?? $customer->customer_name }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $customer->phone ?? $customer->customer_phone ?? '-' }}</div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $customer->address ?? $customer->customer_address ?? '-' }}</p>
                        </div>
                    </div>

                    <!-- Metadata Tags -->
                    <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                        @if($billing)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 font-medium">
                                <i class="fa-solid fa-server text-[9px]"></i> {{ $billing->name }}
                            </span>
                        @endif
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                            {{ $customer->package_name ?? 'Regular' }}
                        </span>
                        @if($customer->odp_name)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 border border-teal-200/80 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800/40 font-mono">
                                <i class="fa-solid fa-sitemap text-[9px]"></i> {{ $customer->odp_name }}
                            </span>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        @if($phoneClean)
                            <a href="https://wa.me/{{ $phoneClean }}" target="_blank"
                               class="flex items-center justify-center gap-1.5 px-3 py-2.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white dark:bg-emerald-950/40 dark:hover:bg-emerald-600 dark:text-emerald-300 dark:hover:text-white rounded-xl border border-emerald-200/80 dark:border-emerald-800/40 text-xs font-semibold transition-all min-h-[44px]">
                                <i class="fa-brands fa-whatsapp text-sm"></i> WA
                            </a>
                        @else
                            <button disabled class="opacity-40 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl text-xs min-h-[44px]">
                                <i class="fa-brands fa-whatsapp text-sm"></i> WA
                            </button>
                        @endif

                        @if($customer->latitude && $customer->longitude)
                            <a href="https://www.google.com/maps/place/{{ $customer->latitude }},{{ $customer->longitude }}" target="_blank"
                               class="flex items-center justify-center gap-1.5 px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold transition-all min-h-[44px]">
                                <i class="fa-solid fa-map-location-dot text-sm"></i> Maps
                            </a>
                        @else
                            <button disabled class="opacity-40 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl text-xs min-h-[44px]">
                                <i class="fa-solid fa-map-location-dot text-sm"></i> Maps
                            </button>
                        @endif

                        <button type="button" 
                                onclick="openCreateTicketForCustomer('{{ $customer->no_services }}', '{{ addslashes($customer->name ?? $customer->customer_name) }}', '{{ $customer->phone ?? $customer->customer_phone }}', '{{ addslashes($customer->address ?? $customer->customer_address) }}', '{{ $customer->billing_node_id ?? $customer->billing_instance_id }}')"
                                class="flex items-center justify-center gap-1.5 px-3 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-sm transition-all min-h-[44px]">
                            <i class="fa-solid fa-plus text-xs"></i> Tiket
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                    <i class="fa-solid fa-user-slash text-3xl mb-2 block text-slate-300 dark:text-slate-700"></i>
                    Belum ada data pelanggan yang cocok.
                </div>
            @endforelse
        </div>

        @if($customers->hasPages())
            <div class="p-3 sm:p-4 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Quick Ticket Creation from Customer List -->
<div id="quickTicketModal" class="hidden fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="document.getElementById('quickTicketModal').classList.add('hidden')"></div>
    <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-t-3xl sm:rounded-2xl shadow-2xl w-full max-w-xl max-h-[92vh] overflow-y-auto z-10 transition-colors">
        <div class="sticky top-0 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-t-3xl sm:rounded-t-2xl flex items-center justify-between z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                    <i class="fa-solid fa-ticket text-base"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Buat Tiket Gangguan</h2>
                    <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Otomatis terisi data pelanggan yang dipilih</p>
                </div>
            </div>
            <button onclick="document.getElementById('quickTicketModal').classList.add('hidden')" 
                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400"
                    aria-label="Tutup modal">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf
            <input type="hidden" name="billing_instance_id" id="qt_billing_id">

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Nama Pelanggan</label>
                <input type="text" name="customer_name" id="qt_customer_name" required readonly class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white min-h-[42px]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">No Layanan</label>
                    <input type="text" name="no_services" id="qt_no_services" required readonly class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-emerald-600 dark:text-emerald-400 font-mono font-bold min-h-[42px]">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">No Telp</label>
                    <input type="text" name="customer_phone" id="qt_customer_phone" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white min-h-[42px]">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Alamat</label>
                <textarea name="customer_address" id="qt_customer_address" rows="2" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Kategori Gangguan</label>
                <input type="text" name="category_name" placeholder="Contoh: FO Putus, Modem Red, Internet Lelet" class="w-full px-3 py-2.5 bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30 min-h-[42px]">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Deskripsi Masalah *</label>
                <textarea name="problem_description" rows="3" required placeholder="Detail laporan masalah pelanggan..." class="w-full px-3 py-2.5 bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-400/30"></textarea>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 pt-3 sm:pt-4 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('quickTicketModal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-slate-400 min-h-[42px] flex items-center justify-center">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs shadow-sm shadow-emerald-600/20 flex items-center justify-center gap-1.5 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[42px]"><i class="fa-solid fa-paper-plane"></i> Buat Tiket</button>
            </div>
        </form>
    </div>
</div>

<script>
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
