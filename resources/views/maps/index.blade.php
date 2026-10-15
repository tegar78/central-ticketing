@extends('layouts.app')

@section('title', 'Peta Lokasi Pelanggan')

@push('styles')
<!-- Leaflet CSS & MarkerCluster CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<link rel="stylesheet" href="{{ asset('css/maps.css') }}?v={{ filemtime(public_path('css/maps.css')) }}">
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden transition-colors">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 flex-shrink-0">
                    <i class="fa-solid fa-map-location-dot text-base"></i>
                </div>
                <span>Maps Location Pelanggan</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Visualisasi sebaran posisi GPS seluruh pelanggan dan coverage area dari server billing Gayuh.
            </p>
        </div>
        <!-- Right Stat Badges -->
        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Ditandai: <strong class="font-bold">{{ number_format($markedCount) }}</strong></span>
            </div>
            @if(in_array($user->role, ['admin', 'operator']))
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Belum Ditandai: <strong class="font-bold">{{ number_format($unmarkedCount) }}</strong></span>
            </div>
            @endif
        </div>
    </div>

    @php
        $activeBillingId = request('billing_node_id');
        $activeBilling = $activeBillingId && isset($billingMap[$activeBillingId]) ? $billingMap[$activeBillingId] : null;
    @endphp

    <!-- Filter & Map Controls Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-xs space-y-3.5 transition-colors">
        <!-- Row 1: Context Filters (Searchable Billing & Status Pills) + Map Action Controls -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Left: Searchable Billing Selector & Status Pills -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Searchable Billing Instance Combobox -->
                <div class="relative inline-block text-left" id="billingCombobox">
                    <button type="button" 
                            id="billingComboboxBtn" 
                            onclick="toggleBillingDropdown()" 
                            class="inline-flex items-center justify-between gap-2.5 w-60 sm:w-64 px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xs text-xs font-semibold text-slate-800 dark:text-slate-200 hover:border-emerald-500 hover:bg-slate-50 dark:hover:bg-slate-700/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer billing-combobox-btn"
                            aria-haspopup="listbox" 
                            aria-expanded="false"
                            title="Filter berdasarkan Server Billing">
                        <div class="flex items-center gap-2 truncate min-w-0">
                            <i class="fa-solid fa-server text-emerald-500 text-xs shrink-0"></i>
                            <span id="billingComboboxLabel" class="truncate font-semibold text-slate-800 dark:text-slate-200">
                                {{ $activeBilling ? '[' . $activeBilling->tenant_code . '] ' . $activeBilling->name : 'Semua Server Billing' }}
                            </span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 dark:text-slate-500 shrink-0 ml-1 transition-transform duration-200" id="billingComboboxChevron"></i>
                    </button>

                    <!-- Dropdown Menu with Live Search -->
                    <div id="billingComboboxMenu" 
                         class="hidden absolute left-0 top-full mt-1.5 w-80 max-w-[90vw] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl z-50 overflow-hidden billing-dropdown-menu"
                         role="listbox">
                        <!-- Search Input Header -->
                        <div class="p-2.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-100 dark:border-slate-800">
                            <div class="relative flex items-center">
                                <i class="fa-solid fa-magnifying-glass absolute left-2.5 text-slate-400 text-xs pointer-events-none"></i>
                                <input type="text" 
                                       id="billingSearchInput" 
                                       placeholder="Cari server billing / kode..." 
                                       autocomplete="off"
                                       oninput="filterBillingList(this.value)"
                                       onkeydown="handleBillingSearchKeydown(event)"
                                       class="w-full pl-7 pr-7 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <button type="button" 
                                        id="clearBillingSearchBtn" 
                                        onclick="clearBillingSearch()" 
                                        class="hidden absolute right-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs p-1 cursor-pointer">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable Options List -->
                        <div id="billingOptionsList" class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 py-1">
                            <!-- Option: Semua Server Billing -->
                            <div class="billing-option-row px-3 py-2 flex items-center justify-between text-xs cursor-pointer transition-colors {{ !$activeBillingId ? 'bg-emerald-50/70 dark:bg-emerald-950/40 font-bold text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-emerald-50/50 dark:hover:bg-slate-800' }}"
                                 data-id="" 
                                 data-search="semua server billing all default"
                                 onclick="selectBillingOption('', 'Semua Server Billing')">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-network-wired text-[11px] text-slate-400"></i>
                                    <span>Semua Server Billing</span>
                                </div>
                                @if(!$activeBillingId)
                                    <i class="fa-solid fa-check text-emerald-600 dark:text-emerald-400 text-xs"></i>
                                @endif
                            </div>

                            <!-- Each Billing Instance -->
                            @foreach($billingInstances as $b)
                                <div class="billing-option-row px-3 py-2 flex items-center justify-between text-xs cursor-pointer transition-colors {{ $activeBillingId == $b->id ? 'bg-emerald-50/70 dark:bg-emerald-950/40 font-bold text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300 hover:bg-emerald-50/50 dark:hover:bg-slate-800' }}"
                                     data-id="{{ $b->id }}"
                                     data-search="{{ strtolower($b->tenant_code . ' ' . $b->name) }}"
                                     onclick="selectBillingOption('{{ $b->id }}', '[{{ $b->tenant_code }}] {{ $b->name }}')">
                                    <div class="flex items-center gap-2 truncate min-w-0">
                                        <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 shrink-0">
                                            {{ $b->tenant_code }}
                                        </span>
                                        <span class="truncate">{{ $b->name }}</span>
                                    </div>
                                    @if($activeBillingId == $b->id)
                                        <i class="fa-solid fa-check text-emerald-600 dark:text-emerald-400 text-xs shrink-0"></i>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Empty Search Result State -->
                        <div id="billingNoResults" class="hidden p-4 text-center text-xs text-slate-400 dark:text-slate-500">
                            <i class="fa-solid fa-circle-question text-base mb-1 block opacity-60"></i>
                            Tidak ada server billing yang cocok
                        </div>
                    </div>
                </div>

                <span class="w-px h-6 bg-slate-200 dark:bg-slate-700 hidden sm:inline-block"></span>

                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 mr-0.5 hidden xl:inline uppercase tracking-wider">Status:</span>
                    
                    <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'all'])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all shadow-xs {{ !request('status') || request('status') === 'all' ? 'bg-slate-900 text-white border-slate-900 dark:bg-white dark:text-slate-900 dark:border-white pill-active-all' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 pill-inactive' }} map-status-pill">
                        Semua <span class="ml-1 opacity-80 font-normal">({{ number_format($statusCounts['all']) }})</span>
                    </a>

                    <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'active'])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all shadow-xs {{ request('status') === 'active' ? 'bg-emerald-600 text-white border-emerald-600 shadow-emerald-600/20 pill-active-active' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-emerald-300 dark:hover:border-emerald-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 pill-inactive' }} map-status-pill">
                        <span class="w-1.5 h-1.5 rounded-full {{ request('status') === 'active' ? 'bg-white' : 'bg-emerald-500' }}"></span>
                        Aktif <span class="opacity-80 font-normal">({{ number_format($statusCounts['active']) }})</span>
                    </a>

                    <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'isolated'])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all shadow-xs {{ request('status') === 'isolated' ? 'bg-amber-600 text-white border-amber-600 shadow-amber-600/20 pill-active-isolated' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 pill-inactive' }} map-status-pill">
                        <span class="w-1.5 h-1.5 rounded-full {{ request('status') === 'isolated' ? 'bg-white' : 'bg-amber-500' }}"></span>
                        Isolir <span class="opacity-80 font-normal">({{ number_format($statusCounts['isolated']) }})</span>
                    </a>

                    <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'inactive'])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all shadow-xs {{ request('status') === 'inactive' ? 'bg-rose-600 text-white border-rose-600 shadow-rose-600/20 pill-active-inactive' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 pill-inactive' }} map-status-pill">
                        <span class="w-1.5 h-1.5 rounded-full {{ request('status') === 'inactive' ? 'bg-white' : 'bg-rose-500' }}"></span>
                        Non-Aktif <span class="opacity-80 font-normal">({{ number_format($statusCounts['inactive']) }})</span>
                    </a>

                    <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'free'])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all shadow-xs {{ request('status') === 'free' ? 'bg-sky-600 text-white border-sky-600 shadow-sky-600/20 pill-active-free' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-sky-300 dark:hover:border-sky-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 pill-inactive' }} map-status-pill">
                        <span class="w-1.5 h-1.5 rounded-full {{ request('status') === 'free' ? 'bg-white' : 'bg-sky-500' }}"></span>
                        Free <span class="opacity-80 font-normal">({{ number_format($statusCounts['free']) }})</span>
                    </a>
                </div>
            </div>

            <!-- Right: Action Controls (Utility Buttons) -->
            <div class="flex items-center gap-1.5 shrink-0 ml-auto lg:ml-0">
                <!-- Pusatkan Peta -->
                <button type="button" onclick="centerMapOnMarkers()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold shadow-xs transition-all cursor-pointer whitespace-nowrap map-action-btn" title="Pusatkan peta ke seluruh titik pelanggan">
                    <i class="fa-solid fa-crosshairs text-emerald-500"></i> <span>Pusatkan</span>
                </button>

                <!-- Lokasi Saya -->
                <button type="button" onclick="locateUserPosition()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold shadow-xs transition-all cursor-pointer whitespace-nowrap map-action-btn" title="Deteksi posisi GPS perangkat saya">
                    <i class="fa-solid fa-location-arrow text-teal-500"></i> <span>Lokasi Saya</span>
                </button>

                <!-- Toggle Layer Satelit -->
                <button type="button" onclick="toggleSatelliteLayer()" id="satelliteToggleBtn" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold shadow-xs transition-all cursor-pointer whitespace-nowrap map-action-btn" title="Ganti tampilan peta satelit / default">
                    <i class="fa-solid fa-layer-group text-sky-500"></i> <span id="layerNameText">Satelit</span>
                </button>

                @if(in_array($user->role, ['admin', 'operator']))
                <!-- Sync Data Button -->
                <form action="{{ route('customers.syncBilling') }}" method="POST" class="inline">
                    @csrf
                    @php
                        $syncTenant = $activeBilling ? $activeBilling->tenant_code : 'all';
                    @endphp
                    <input type="hidden" name="tenant_code" value="{{ $syncTenant }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-emerald-600 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-xs transition-all cursor-pointer whitespace-nowrap map-action-btn btn-sync" title="Sinkronisasi data pelanggan dari server billing">
                        <i class="fa-solid fa-rotate"></i> <span>Sync Tabel</span>
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Row 2: Customer Live Search & Filter Feedback -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Search Input (No "Tekan /" badge) -->
            <div class="relative w-full sm:w-80 md:w-96 lg:w-[420px] shrink-0">
                <div class="relative flex items-center">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 text-slate-400 dark:text-slate-500 text-xs pointer-events-none"></i>
                    <input type="text" 
                           id="mapSearchInput" 
                           placeholder="Cari nama pelanggan di peta (Enter: filter)..." 
                           autocomplete="off"
                           class="w-full pl-9 pr-9 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-xs map-search-input-field">
                    
                    <button type="button" 
                            id="clearMapSearchBtn" 
                            onclick="clearMapSearch()" 
                            class="hidden absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs p-1 rounded-md cursor-pointer transition-colors" 
                            title="Bersihkan pencarian">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>

                <!-- Autocomplete Dropdown List -->
                <div id="mapSearchResults" class="hidden absolute left-0 right-0 top-full mt-1.5 max-h-80 overflow-y-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl z-50 divide-y divide-slate-100 dark:divide-slate-800">
                </div>
            </div>

            <!-- Active Filter Feedback & Default Stats -->
            <div class="flex items-center gap-3">
                <!-- Active Search Filter Badge -->
                <div id="activeFilterBadge" class="hidden items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/60 font-semibold text-xs">
                        <i class="fa-solid fa-filter text-[10px]"></i>
                        <span id="activeFilterText">Menampilkan hasil filter</span>
                    </span>
                    <button type="button" onclick="clearMapFilter()" class="px-2.5 py-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 bg-emerald-50/50 hover:bg-emerald-100/50 dark:bg-emerald-950/30 rounded-lg transition-colors cursor-pointer">
                        Reset Filter
                    </button>
                </div>

                <!-- Default Map Info Text -->
                <div id="mapInfoText" class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-map-pin text-emerald-500 text-[11px]"></i>
                    <span>Menampilkan <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ number_format($markedCount) }}</strong> titik pelanggan di peta</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden relative">
        <div id="customerMap" class="w-full h-[520px] sm:h-[600px] z-10"></div>
        
        <!-- Floating Legend on Map -->
        <div class="absolute bottom-4 left-4 z-20 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-md text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800 dark:text-slate-200 mb-1 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-info text-emerald-500"></i> Keterangan Status:
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>Aktif</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <span>Isolir</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                <span>Non-Aktif</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                <span>Free / Promo</span>
            </div>
        </div>
    </div>

    @if(in_array($user->role, ['admin', 'operator']))
    {{-- Bottom Section: Data Pelanggan yang Belum Ditandai Maps (Hanya Admin & Operator) --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-colors">
        <!-- Card Header -->
        <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                        <span>Data Pelanggan yang Belum Ditandai Maps</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40">
                            {{ number_format($unmarkedCount) }} Pelanggan
                        </span>
                    </h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                        Pelanggan di bawah ini belum memiliki titik koordinat GPS. Klik tombol Tandai untuk menentukan posisi pelanggan.
                    </p>
                </div>
            </div>
        </div>

        <!-- Filter & Search Sub-bar -->
        <div class="p-4 bg-slate-50/50 dark:bg-slate-800/30 border-b border-slate-200/80 dark:border-slate-800">
            <form action="{{ route('maps.index') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <!-- Preserve existing map filters -->
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('billing_node_id'))
                    <input type="hidden" name="billing_node_id" value="{{ request('billing_node_id') }}">
                @endif
                
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-semibold text-slate-400 mr-1"><i class="fa-solid fa-filter text-emerald-500 mr-1"></i>Status:</span>
                    <a href="{{ request()->fullUrlWithQuery(['unmarked_status' => 'all', 'unmarked_page' => 1]) }}" 
                       class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ !request('unmarked_status') || request('unmarked_status') === 'all' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-emerald-500' }}">
                        Semua @if(isset($unmarkedStatusCounts['all']))<span class="ml-0.5 opacity-85 text-[11px]">({{ number_format($unmarkedStatusCounts['all']) }})</span>@endif
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['unmarked_status' => 'active', 'unmarked_page' => 1]) }}" 
                       class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('unmarked_status') === 'active' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-emerald-500' }}">
                        Aktif @if(isset($unmarkedStatusCounts['active']))<span class="ml-0.5 opacity-85 text-[11px]">({{ number_format($unmarkedStatusCounts['active']) }})</span>@endif
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['unmarked_status' => 'isolated', 'unmarked_page' => 1]) }}" 
                       class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('unmarked_status') === 'isolated' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-amber-500' }}">
                        Isolir @if(isset($unmarkedStatusCounts['isolated']))<span class="ml-0.5 opacity-85 text-[11px]">({{ number_format($unmarkedStatusCounts['isolated']) }})</span>@endif
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['unmarked_status' => 'inactive', 'unmarked_page' => 1]) }}" 
                       class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('unmarked_status') === 'inactive' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-rose-500' }}">
                        Non-Aktif @if(isset($unmarkedStatusCounts['inactive']))<span class="ml-0.5 opacity-85 text-[11px]">({{ number_format($unmarkedStatusCounts['inactive']) }})</span>@endif
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['unmarked_status' => 'free', 'unmarked_page' => 1]) }}" 
                       class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('unmarked_status') === 'free' ? 'bg-sky-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-sky-500' }}">
                        Free @if(isset($unmarkedStatusCounts['free']))<span class="ml-0.5 opacity-85 text-[11px]">({{ number_format($unmarkedStatusCounts['free']) }})</span>@endif
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:w-64">
                        <input type="text" name="unmarked_search" value="{{ request('unmarked_search') }}" placeholder="Cari nama, no layanan, alamat..." 
                               class="w-full pl-8 pr-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500">
                        <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                    </div>
                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold">
                        Cari
                    </button>
                    @if(request('unmarked_search') || request('unmarked_status'))
                    <a href="{{ route('maps.index', request()->only(['status', 'billing_node_id'])) }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">No</th>
                        <th class="px-4 py-3">No Layanan</th>
                        <th class="px-4 py-3">Nama Pelanggan</th>
                        <th class="px-4 py-3">No Telp</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Coverage Area / ODP</th>
                        <th class="px-4 py-3">Alamat</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800">
                    @forelse($unmarkedCustomers as $index => $customer)
                        @php
                            $phoneClean = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : null;
                            if ($phoneClean && str_starts_with($phoneClean, '0')) {
                                $phoneClean = '62' . substr($phoneClean, 1);
                            }
                            $cStatus = strtolower($customer->status ?? 'active');
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-4 py-3.5 text-center font-medium text-slate-400">
                                {{ $unmarkedCustomers->firstItem() + $index }}.
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs">{{ $customer->no_services }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900 dark:text-white">
                                {{ $customer->name }}
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($phoneClean)
                                    <a href="https://wa.me/{{ $phoneClean }}" target="_blank" class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-500 font-mono text-xs">
                                        <i class="fa-brands fa-whatsapp text-sm"></i>
                                        <span>+{{ $phoneClean }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if($cStatus === 'active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40">Aktif</span>
                                @elseif($cStatus === 'isolated')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40">Isolir</span>
                                @elseif($cStatus === 'free')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/40">Free</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($customer->odp_name)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-mono text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/40 border border-teal-200/80 dark:border-teal-800/40 px-2 py-0.5 rounded-md">
                                        <i class="fa-solid fa-sitemap text-[9px]"></i> {{ $customer->odp_name }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 max-w-xs truncate text-slate-500 dark:text-slate-400 text-[11px]" title="{{ $customer->address }}">
                                {{ $customer->address ?? '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <button type="button" 
                                        data-id="{{ $customer->id }}"
                                        data-name="{{ $customer->name }}"
                                        data-no-services="{{ $customer->no_services }}"
                                        data-address="{{ $customer->address ?? '' }}"
                                        onclick="openTandaiModalFromEl(this)"
                                        class="px-2.5 py-1 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white dark:bg-amber-950/40 dark:hover:bg-amber-600 dark:text-amber-300 dark:hover:text-white rounded-lg border border-amber-200 dark:border-amber-800/50 transition-all text-xs font-semibold flex items-center gap-1 mx-auto">
                                    <i class="fa-solid fa-location-crosshairs text-xs"></i> Tandai
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-circle-check text-3xl mb-2 block text-emerald-500"></i>
                                Semua pelanggan telah memiliki titik koordinat GPS di peta!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($unmarkedCustomers->hasPages())
        <div class="p-4 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60">
            {{ $unmarkedCustomers->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Tandai Koordinat Pelanggan -->
<div id="tandaiModal" onclick="if(event.target === this) closeTandaiModal()" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-5 space-y-4 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3">
            <h3 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-map-pin text-emerald-500"></i> Tandai Titik GPS Pelanggan
            </h3>
            <button onclick="closeTandaiModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400 flex items-center justify-center cursor-pointer transition-colors">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="tandaiForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Pelanggan</label>
                <input type="text" id="modalCustomerName" readonly class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-semibold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">No Layanan</label>
                    <input type="text" id="modalNoServices" readonly class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Alamat</label>
                    <input type="text" id="modalCustomerAddress" readonly class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-500 dark:text-slate-400 truncate">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Latitude (Lintang) *</label>
                    <input type="text" name="latitude" id="modalLatitude" required placeholder="-6.123456" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Longitude (Bujur) *</label>
                    <input type="text" name="longitude" id="modalLongitude" required placeholder="106.123456" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200/80 dark:border-emerald-800/40 text-[11px] text-emerald-800 dark:text-emerald-300 flex items-start gap-2">
                <i class="fa-solid fa-lightbulb text-emerald-600 mt-0.5"></i>
                <span>Tips: Anda bisa menyalin koordinat dari Google Maps (contoh: <code>-6.133934, 106.675079</code>) dan memasukkannya ke kolom di atas.</span>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="closeTandaiModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 cursor-pointer transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-sm shadow-emerald-600/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Koordinat
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Titik Koordinat GPS Pelanggan (Pelanggan yang Sudah Ditandai) -->
<div id="editCoordinatesModal" onclick="if(event.target === this) closeEditModal()" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-5 space-y-4 transition-colors">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3">
            <h3 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <span>Edit Titik Koordinat GPS Pelanggan</span>
            </h3>
            <button type="button" onclick="closeEditModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400 flex items-center justify-center transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="editCoordinatesForm" onsubmit="handleEditCoordinatesSubmit(event)" class="space-y-4">
            <input type="hidden" id="editCustomerId" value="">

            <!-- Customer Summary Card -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/70 dark:border-slate-800 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-900 dark:text-white text-sm" id="editCustomerName">-</span>
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs" id="editCustomerNoServices">-</span>
                </div>
                <div class="text-slate-500 dark:text-slate-400 flex items-start gap-1.5 text-[11px]" id="editCustomerAddressRow">
                    <i class="fa-solid fa-location-dot text-slate-400 mt-0.5 flex-shrink-0 text-[10px]"></i>
                    <span id="editCustomerAddress" class="truncate">-</span>
                </div>
            </div>

            <!-- Smart Paste Helper -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                    <span>Tempel Link / Koordinat Google Maps</span>
                    <span class="text-[10px] text-slate-400 font-normal">Auto-detect format</span>
                </label>
                <div class="relative">
                    <input type="text" id="editSmartPasteInput" oninput="handleSmartPaste(this.value)" placeholder="Contoh: -6.133934, 106.675079 atau link maps..." 
                           class="w-full pl-8 pr-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 transition-all font-mono">
                    <i class="fa-solid fa-paste absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                </div>
                <div id="smartPasteFeedback" class="hidden mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check"></i> <span>Koordinat berhasil diekstrak otomatis!</span>
                </div>
            </div>

            <!-- Latitude & Longitude Inputs -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Latitude (Lintang) *</label>
                    <input type="text" name="latitude" id="editModalLatitude" required placeholder="-6.123456" 
                           class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Longitude (Bujur) *</label>
                    <input type="text" name="longitude" id="editModalLongitude" required placeholder="106.123456" 
                           class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-amber-500">
                </div>
            </div>

            <!-- Quick Map Adjustment Buttons -->
            <div class="flex items-center gap-2 pt-0.5">
                <button type="button" onclick="startPickingOnMap()" class="flex-1 py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                    <i class="fa-solid fa-crosshairs text-amber-500"></i> <span>Pilih Titik di Peta</span>
                </button>
                <button type="button" onclick="useCurrentLocationForEdit()" class="flex-1 py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                    <i class="fa-solid fa-location-arrow text-teal-500"></i> <span>Lokasi Saya</span>
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" id="saveEditCoordsBtn" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm shadow-amber-500/20 flex items-center gap-1.5 transition-all cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i> <span>Simpan Koordinat</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Banner when "Pilih Titik di Peta" is active -->
<div id="pickingBanner" class="hidden fixed top-6 inset-x-0 mx-auto w-fit z-50 bg-slate-900/95 dark:bg-slate-800/95 text-white px-5 py-3 rounded-2xl shadow-2xl border border-amber-500/50 flex items-center gap-4">
    <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm flex-shrink-0 animate-pulse">
        <i class="fa-solid fa-map-pin"></i>
    </div>
    <div>
        <div class="text-xs font-bold text-white">Mode Pemilihan Titik Aktif</div>
        <div class="text-[11px] text-slate-300">Klik lokasi baru di peta untuk menentukan koordinat pelanggan.</div>
    </div>
    <button type="button" onclick="cancelPickingOnMap()" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded-lg text-xs font-semibold text-slate-200 transition-colors ml-2">
        Batal
    </button>
</div>
@endif

<!-- Raw Customer Data for Leaflet (JSON parsed safely to prevent linter errors) -->
<script type="application/json" id="customersData">
    @json($markedCustomers)
</script>
<script type="application/json" id="billingMapData">
    @json($billingMap)
</script>
@endsection

@push('scripts')
<!-- Leaflet & MarkerCluster JS CDN -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
let map = null;
let markerClusterGroup = null;
let osmLayer = null;
let satelliteLayer = null;
let currentLayerType = 'osm';
let isPickingCoordinates = false;
let pickingClickListener = null;

const canEditCoordinates = {{ in_array($user->role, ['admin', 'operator']) ? 'true' : 'false' }};
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

const customerMarkersMap = new Map();
const customersDataMap = new Map();
let customersList = [];
let bMap = {};
let searchDebounceTimer = null;

document.addEventListener('DOMContentLoaded', function() {
    initCustomerMap();
    initMapSearch();
});

// XSS sanitization helper
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

// Generate Popup HTML Content
function buildPopupContent(cust) {
    const lat = parseFloat(cust.latitude);
    const lng = parseFloat(cust.longitude);
    const statusClass = (cust.status || 'active').toLowerCase();

    // Clean phone
    let phoneClean = cust.phone ? String(cust.phone).replace(/[^0-9]/g, '') : '';
    if (phoneClean && phoneClean.startsWith('0')) {
        phoneClean = '62' + phoneClean.substring(1);
    }

    const safeNoServices = escapeHtml(cust.no_services);
    const safeName = escapeHtml(cust.name);
    const safeAddress = escapeHtml(cust.address || '-');
    const safeStatus = escapeHtml(cust.status || 'Aktif');
    const safeOdp = escapeHtml(cust.odp_name || '');
    const tenantTag = cust.billing_node_id && bMap[cust.billing_node_id] 
        ? `[${escapeHtml(bMap[cust.billing_node_id].tenant_code)}]` 
        : '';

    return `
        <div class="p-3.5 space-y-2 text-xs font-sans min-w-[240px]">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                    ${safeNoServices} ${tenantTag ? `<span class="text-slate-400 dark:text-slate-500 font-normal text-[10px] ml-1">${tenantTag}</span>` : ''}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                    statusClass === 'active' ? 'bg-emerald-50 text-emerald-700' :
                    statusClass === 'isolated' ? 'bg-amber-50 text-amber-700' :
                    statusClass === 'free' ? 'bg-sky-50 text-sky-700' : 'bg-rose-50 text-rose-700'
                }">${safeStatus}</span>
            </div>
            <div>
                <div class="font-bold text-slate-900 dark:text-white text-sm">${safeName}</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">${safeAddress}</div>
            </div>
            ${safeOdp ? `
            <div class="inline-flex items-center gap-1 text-[10px] font-mono text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/40 px-2 py-0.5 rounded">
                <i class="fa-solid fa-sitemap"></i> ${safeOdp}
            </div>` : ''}
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1.5 flex-wrap">
                ${phoneClean ? `
                <a href="https://wa.me/${encodeURIComponent(phoneClean)}" target="_blank" class="popup-wa-btn flex-1 py-1.5 px-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-semibold flex items-center justify-center gap-1.5 text-center shadow-sm" style="color: #ffffff !important; background-color: #10B981 !important;">
                    <i class="fa-brands fa-whatsapp text-sm" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">WA</span>
                </a>` : ''}
                <a href="https://www.google.com/maps/place/${encodeURIComponent(lat)},${encodeURIComponent(lng)}" target="_blank" class="popup-maps-btn py-1.5 px-2.5 rounded-lg text-[11px] font-medium flex items-center justify-center gap-1.5 border border-slate-200 dark:border-slate-700">
                    <i class="fa-solid fa-location-arrow text-emerald-500"></i> <span>Maps</span>
                </a>
                ${canEditCoordinates ? `
                <button type="button" onclick="openEditModal(${cust.id})" class="popup-edit-btn py-1.5 px-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[11px] font-semibold flex items-center justify-center gap-1.5 text-center shadow-sm transition-colors cursor-pointer" title="Edit Titik Koordinat GPS Pelanggan">
                    <i class="fa-solid fa-pen-to-square"></i> <span>Edit Titik</span>
                </button>` : ''}
            </div>
        </div>
    `;
}

function initCustomerMap() {
    const mapContainer = document.getElementById('customerMap');
    if (!mapContainer || typeof L === 'undefined') return;

    // Default center to Indonesia (Tangerang/Jakarta area as common default)
    const defaultLat = -6.175392;
    const defaultLng = 106.827153;
    const defaultZoom = 11;

    // Initialize map
    map = L.map('customerMap', {
        zoomControl: true,
        attributionControl: true
    }).setView([defaultLat, defaultLng], defaultZoom);

    // Tile layers
    osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Google Satellite / ESRI Satellite
    satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: '&copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
    });

    // Marker cluster group with emerald theme
    markerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
    });

    // Parse JSON customer data & billing map
    const dataElement = document.getElementById('customersData');
    customersList = dataElement ? JSON.parse(dataElement.textContent || '[]') : [];

    const bMapElement = document.getElementById('billingMapData');
    bMap = bMapElement ? JSON.parse(bMapElement.textContent || '{}') : {};

    const bounds = [];

    customersList.forEach(cust => {
        let lat = parseFloat(cust.latitude);
        let lng = parseFloat(cust.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        // Auto-swap if inverted (e.g. lat > 6 and lng < 10)
        if (lat > 6 && lat <= 141 && lng >= -11 && lng <= 6) {
            const temp = lat;
            lat = lng;
            lng = temp;
        }

        // Only include in bounds if within valid Indonesian coordinates
        if (lat >= -11 && lat <= 6 && lng >= 95 && lng <= 141) {
            bounds.push([lat, lng]);
        }

        const statusClass = (cust.status || 'active').toLowerCase();
        
        // Custom colored pin marker
        const pinIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="custom-map-pin ${statusClass}" style="width: 26px; height: 26px;"><i class="fa-solid fa-user text-[10px]"></i></div>`,
            iconSize: [26, 26],
            iconAnchor: [13, 13],
            popupAnchor: [0, -14]
        });

        const marker = L.marker([lat, lng], { icon: pinIcon });
        marker.bindPopup(buildPopupContent(cust));

        // Store lookup references
        customerMarkersMap.set(Number(cust.id), marker);
        customersDataMap.set(Number(cust.id), cust);

        markerClusterGroup.addLayer(marker);
    });

    map.addLayer(markerClusterGroup);

    // If we have bounds, fit map to all valid markers
    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
    } else {
        map.setView([defaultLat, defaultLng], defaultZoom);
    }
}

// ==========================================
// SEARCHABLE BILLING COMBOBOX
// ==========================================
function toggleBillingDropdown() {
    const menu = document.getElementById('billingComboboxMenu');
    const btn = document.getElementById('billingComboboxBtn');
    const chevron = document.getElementById('billingComboboxChevron');
    const input = document.getElementById('billingSearchInput');
    if (!menu) return;

    const isHidden = menu.classList.contains('hidden');
    if (isHidden) {
        menu.classList.remove('hidden');
        if (btn) btn.setAttribute('aria-expanded', 'true');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        if (input) {
            input.value = '';
            filterBillingList('');
            setTimeout(() => input.focus(), 50);
        }
    } else {
        closeBillingDropdown();
    }
}

function closeBillingDropdown() {
    const menu = document.getElementById('billingComboboxMenu');
    const btn = document.getElementById('billingComboboxBtn');
    const chevron = document.getElementById('billingComboboxChevron');
    if (menu) menu.classList.add('hidden');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    if (chevron) chevron.style.transform = 'rotate(0deg)';
}

function filterBillingList(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#billingOptionsList .billing-option-row');
    const noResults = document.getElementById('billingNoResults');
    const clearBtn = document.getElementById('clearBillingSearchBtn');

    if (clearBtn) {
        if (q.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }

    let matchCount = 0;
    rows.forEach(row => {
        const searchText = row.getAttribute('data-search') || '';
        if (!q || searchText.includes(q)) {
            row.style.display = 'flex';
            matchCount++;
        } else {
            row.style.display = 'none';
        }
    });

    if (noResults) {
        if (matchCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }
}

function clearBillingSearch() {
    const input = document.getElementById('billingSearchInput');
    if (input) {
        input.value = '';
        input.focus();
        filterBillingList('');
    }
}

function handleBillingSearchKeydown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        closeBillingDropdown();
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const firstVisible = Array.from(document.querySelectorAll('#billingOptionsList .billing-option-row')).find(row => row.style.display !== 'none');
        if (firstVisible) {
            firstVisible.click();
        }
    }
}

function selectBillingOption(id, label) {
    closeBillingDropdown();
    const currentUrl = new URL(window.location.href);
    if (id) {
        currentUrl.searchParams.set('billing_node_id', id);
    } else {
        currentUrl.searchParams.delete('billing_node_id');
    }
    // Reset pagination for unmarked table if present
    currentUrl.searchParams.delete('unmarked_page');
    window.location.href = currentUrl.toString();
}

// Click outside listener for billing combobox
document.addEventListener('click', function(e) {
    const combobox = document.getElementById('billingCombobox');
    if (combobox && !combobox.contains(e.target)) {
        closeBillingDropdown();
    }
});

// ==========================================
// PENCARIAN PELANGGAN DI MAPS (MARKED)
// ==========================================
function initMapSearch() {
    const searchInput = document.getElementById('mapSearchInput');
    const resultsContainer = document.getElementById('mapSearchResults');
    const clearBtn = document.getElementById('clearMapSearchBtn');

    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchDebounceTimer);
        const query = e.target.value.trim();

        if (query.length > 0) {
            if (clearBtn) clearBtn.classList.remove('hidden');
        } else {
            if (clearBtn) clearBtn.classList.add('hidden');
            if (resultsContainer) resultsContainer.classList.add('hidden');
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            performMarkedCustomerSearch(query);
        }, 150);
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                filterMarkersOnMap(query);
            }
        } else if (e.key === 'Escape') {
            if (resultsContainer) resultsContainer.classList.add('hidden');
        }
    });

    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        if (resultsContainer && !resultsContainer.contains(e.target) && e.target !== searchInput) {
            resultsContainer.classList.add('hidden');
        }
    });
}

function highlightMatchText(text, query) {
    if (!text || !query) return escapeHtml(text || '');
    const escapedText = escapeHtml(text);
    const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escapedQuery})`, 'gi');
    return escapedText.replace(regex, '<mark class="bg-amber-200 dark:bg-amber-900/60 dark:text-amber-200 text-slate-900 rounded px-0.5 font-bold">$1</mark>');
}

function performMarkedCustomerSearch(query) {
    const resultsContainer = document.getElementById('mapSearchResults');
    if (!resultsContainer) return;

    const q = query.toLowerCase();
    const matches = [];

    for (const cust of customersList) {
        const name = (cust.name || '').toLowerCase();
        const noServices = (cust.no_services || '').toLowerCase();
        const address = (cust.address || '').toLowerCase();
        const odp = (cust.odp_name || '').toLowerCase();

        if (name.includes(q) || noServices.includes(q) || address.includes(q) || odp.includes(q)) {
            matches.push(cust);
            if (matches.length >= 12) break; // Limit dropdown to top 12 matches for speed
        }
    }

    if (matches.length === 0) {
        resultsContainer.innerHTML = `
            <div class="p-4 text-center text-slate-400 dark:text-slate-500">
                <i class="fa-solid fa-magnifying-glass mb-1.5 text-base block text-slate-300 dark:text-slate-600"></i>
                <div class="font-medium">Tidak ada pelanggan ditandai yang cocok</div>
                <div class="text-[11px] text-slate-400 mt-0.5">Cari dengan nama, no layanan, alamat, atau ODP.</div>
            </div>
        `;
        resultsContainer.classList.remove('hidden');
        return;
    }

    let html = `
        <div class="px-3 py-2 bg-slate-50 dark:bg-slate-800/80 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider flex items-center justify-between border-b border-slate-100 dark:border-slate-800">
            <span>Ditemukan ${matches.length} pelanggan</span>
            <span class="text-[9px] font-normal normal-case text-emerald-600 dark:text-emerald-400">Klik untuk pusatkan</span>
        </div>
    `;

    matches.forEach(cust => {
        const statusClass = (cust.status || 'active').toLowerCase();
        const tenantTag = cust.billing_node_id && bMap[cust.billing_node_id] 
            ? `[${escapeHtml(bMap[cust.billing_node_id].tenant_code)}]` 
            : '';

        const highlightedName = highlightMatchText(cust.name, query);
        const highlightedNoServices = highlightMatchText(cust.no_services, query);

        html += `
            <div onclick="selectCustomerFromSearch(${cust.id})" class="p-2.5 hover:bg-emerald-50/70 dark:hover:bg-slate-800/70 cursor-pointer transition-colors flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-bold text-slate-900 dark:text-white text-xs">${highlightedName}</span>
                        <span class="font-mono text-[10px] font-bold text-emerald-600 dark:text-emerald-400">${highlightedNoServices}</span>
                        ${tenantTag ? `<span class="text-[10px] text-slate-400 font-mono">${tenantTag}</span>` : ''}
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                        <i class="fa-solid fa-location-dot text-[9px] text-slate-400 mr-1"></i>${escapeHtml(cust.address || '-')}
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider ${
                        statusClass === 'active' ? 'bg-emerald-50 text-emerald-700' :
                        statusClass === 'isolated' ? 'bg-amber-50 text-amber-700' :
                        statusClass === 'free' ? 'bg-sky-50 text-sky-700' : 'bg-rose-50 text-rose-700'
                    }">${escapeHtml(cust.status || 'Aktif')}</span>
                    ${cust.odp_name ? `<span class="text-[9px] font-mono text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/40 px-1 rounded">${escapeHtml(cust.odp_name)}</span>` : ''}
                </div>
            </div>
        `;
    });

    resultsContainer.innerHTML = html;
    resultsContainer.classList.remove('hidden');
}

function selectCustomerFromSearch(customerId) {
    const cust = customersDataMap.get(Number(customerId));
    const marker = customerMarkersMap.get(Number(customerId));
    const resultsContainer = document.getElementById('mapSearchResults');
    if (resultsContainer) resultsContainer.classList.add('hidden');

    if (!cust || !marker || !map || !markerClusterGroup) return;

    const lat = parseFloat(cust.latitude);
    const lng = parseFloat(cust.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    // Smoothly fly to customer position
    map.flyTo([lat, lng], 18, { animate: true, duration: 1.2 });

    // Zoom into cluster and open popup
    markerClusterGroup.zoomToShowLayer(marker, function() {
        marker.openPopup();

        // Trigger pulse highlight animation on the marker pin
        const iconEl = marker.getElement();
        if (iconEl) {
            const pinDiv = iconEl.querySelector('.custom-map-pin');
            if (pinDiv) {
                pinDiv.classList.add('marker-highlight-pulse');
                setTimeout(() => pinDiv.classList.remove('marker-highlight-pulse'), 3600);
            }
        }
    });

    // Update search input value with customer name
    const searchInput = document.getElementById('mapSearchInput');
    if (searchInput) {
        searchInput.value = cust.name;
    }
}

function filterMarkersOnMap(query) {
    if (!query) {
        clearMapFilter();
        return;
    }

    const q = query.toLowerCase();
    markerClusterGroup.clearLayers();
    const bounds = [];
    let matchCount = 0;

    customersList.forEach(cust => {
        const name = (cust.name || '').toLowerCase();
        const noServices = (cust.no_services || '').toLowerCase();
        const address = (cust.address || '').toLowerCase();
        const odp = (cust.odp_name || '').toLowerCase();

        if (name.includes(q) || noServices.includes(q) || address.includes(q) || odp.includes(q)) {
            const marker = customerMarkersMap.get(Number(cust.id));
            if (marker) {
                markerClusterGroup.addLayer(marker);
                let lat = parseFloat(cust.latitude);
                let lng = parseFloat(cust.longitude);
                if (lat > 6 && lat <= 141 && lng >= -11 && lng <= 6) {
                    const temp = lat;
                    lat = lng;
                    lng = temp;
                }
                if (!isNaN(lat) && !isNaN(lng) && lat >= -11 && lat <= 6 && lng >= 95 && lng <= 141) {
                    bounds.push([lat, lng]);
                }
                matchCount++;
            }
        }
    });

    // Show active filter indicator badge & hide default info text
    const badge = document.getElementById('activeFilterBadge');
    const badgeText = document.getElementById('activeFilterText');
    const infoText = document.getElementById('mapInfoText');
    if (badge && badgeText) {
        badgeText.textContent = `Menampilkan ${matchCount} pelanggan ("${query}")`;
        badge.classList.remove('hidden');
        badge.classList.add('inline-flex');
    }
    if (infoText) {
        infoText.classList.add('hidden');
    }

    const resultsContainer = document.getElementById('mapSearchResults');
    if (resultsContainer) resultsContainer.classList.add('hidden');

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 17 });
    } else {
        showMapToast('Tidak ada pelanggan yang cocok dengan kata kunci: ' + query, 'error');
    }
}

function clearMapSearch() {
    clearMapFilter();
}

function clearMapFilter() {
    markerClusterGroup.clearLayers();
    const bounds = [];

    customersList.forEach(cust => {
        const marker = customerMarkersMap.get(Number(cust.id));
        if (marker) {
            markerClusterGroup.addLayer(marker);
            let lat = parseFloat(cust.latitude);
            let lng = parseFloat(cust.longitude);
            if (lat > 6 && lat <= 141 && lng >= -11 && lng <= 6) {
                const temp = lat;
                lat = lng;
                lng = temp;
            }
            if (!isNaN(lat) && !isNaN(lng) && lat >= -11 && lat <= 6 && lng >= 95 && lng <= 141) {
                bounds.push([lat, lng]);
            }
        }
    });

    const badge = document.getElementById('activeFilterBadge');
    const infoText = document.getElementById('mapInfoText');
    if (badge) {
        badge.classList.add('hidden');
        badge.classList.remove('inline-flex');
    }
    if (infoText) {
        infoText.classList.remove('hidden');
    }

    const searchInput = document.getElementById('mapSearchInput');
    if (searchInput) searchInput.value = '';

    const clearBtn = document.getElementById('clearMapSearchBtn');
    if (clearBtn) clearBtn.classList.add('hidden');

    const resultsContainer = document.getElementById('mapSearchResults');
    if (resultsContainer) resultsContainer.classList.add('hidden');

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
    }
}

// ==========================================
// FITUR EDIT KOORDINAT LAT & LNG (MARKED)
// ==========================================
function parseCoordinatesInput(input) {
    if (!input || typeof input !== 'string') return null;
    const trimmed = input.trim();

    // 1. Google Maps @lat,lng format
    const atMatch = trimmed.match(/@(-?\d+\.\d+),\s*(-?\d+\.\d+)/);
    if (atMatch) {
        return { lat: parseFloat(atMatch[1]), lng: parseFloat(atMatch[2]) };
    }

    // 2. Query param q=lat,lng or query=lat,lng or ll=lat,lng
    const qMatch = trimmed.match(/[?&](?:q|query|ll)=(-?\d+\.\d+),\s*(-?\d+\.\d+)/);
    if (qMatch) {
        return { lat: parseFloat(qMatch[1]), lng: parseFloat(qMatch[2]) };
    }

    // 3. Standard "lat, lng" or "lat lng" pair
    const pairMatch = trimmed.match(/(-?\d+\.\d+)[\s,]+(-?\d+\.\d+)/);
    if (pairMatch) {
        const lat = parseFloat(pairMatch[1]);
        const lng = parseFloat(pairMatch[2]);
        if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            return { lat, lng };
        }
    }

    return null;
}

function handleSmartPaste(value) {
    const feedback = document.getElementById('smartPasteFeedback');
    const coords = parseCoordinatesInput(value);

    if (coords) {
        document.getElementById('editModalLatitude').value = coords.lat.toFixed(6);
        document.getElementById('editModalLongitude').value = coords.lng.toFixed(6);
        if (feedback) feedback.classList.remove('hidden');
    } else {
        if (feedback) feedback.classList.add('hidden');
    }
}

function openEditModal(customerId) {
    const cust = customersDataMap.get(Number(customerId));
    const modal = document.getElementById('editCoordinatesModal');
    if (!cust || !modal) return;

    // Close any open popup
    if (map) map.closePopup();

    document.getElementById('editCustomerId').value = cust.id;
    document.getElementById('editCustomerName').textContent = cust.name;
    document.getElementById('editCustomerNoServices').textContent = cust.no_services;
    document.getElementById('editCustomerAddress').textContent = cust.address || '-';
    document.getElementById('editModalLatitude').value = cust.latitude || '';
    document.getElementById('editModalLongitude').value = cust.longitude || '';

    const smartPasteInput = document.getElementById('editSmartPasteInput');
    if (smartPasteInput) smartPasteInput.value = '';
    const feedback = document.getElementById('smartPasteFeedback');
    if (feedback) feedback.classList.add('hidden');

    modal.classList.remove('hidden');
}

function closeEditModal() {
    const modal = document.getElementById('editCoordinatesModal');
    if (modal) modal.classList.add('hidden');

    // Stop picking mode if active without re-opening modal
    stopPickingMode();

    // Clear customer ID and inputs so it never re-opens accidentally
    const idInput = document.getElementById('editCustomerId');
    if (idInput) idInput.value = '';
    const smartPasteInput = document.getElementById('editSmartPasteInput');
    if (smartPasteInput) smartPasteInput.value = '';
    const feedback = document.getElementById('smartPasteFeedback');
    if (feedback) feedback.classList.add('hidden');
}

function stopPickingMode() {
    const banner = document.getElementById('pickingBanner');
    const mapContainer = document.getElementById('customerMap');

    if (banner) banner.classList.add('hidden');
    if (mapContainer) mapContainer.classList.remove('cursor-crosshair-map');

    if (map && pickingClickListener) {
        map.off('click', pickingClickListener);
        pickingClickListener = null;
    }

    isPickingCoordinates = false;
}

function startPickingOnMap() {
    const modal = document.getElementById('editCoordinatesModal');
    const banner = document.getElementById('pickingBanner');
    const mapContainer = document.getElementById('customerMap');

    if (modal) modal.classList.add('hidden');
    if (banner) banner.classList.remove('hidden');
    if (mapContainer) mapContainer.classList.add('cursor-crosshair-map');

    isPickingCoordinates = true;

    // One-time click on map
    if (map) {
        pickingClickListener = function(e) {
            const clickedLat = e.latlng.lat.toFixed(6);
            const clickedLng = e.latlng.lng.toFixed(6);

            document.getElementById('editModalLatitude').value = clickedLat;
            document.getElementById('editModalLongitude').value = clickedLng;

            stopPickingMode();

            // Re-open modal with new coordinates
            if (modal) modal.classList.remove('hidden');
            showMapToast('Koordinat ' + clickedLat + ', ' + clickedLng + ' dipilih dari peta!', 'success');
        };

        map.once('click', pickingClickListener);
    }
}

function cancelPickingOnMap() {
    stopPickingMode();
    const modal = document.getElementById('editCoordinatesModal');
    if (modal && document.getElementById('editCustomerId').value) {
        modal.classList.remove('hidden');
    }
}

function useCurrentLocationForEdit() {
    if (!navigator.geolocation) {
        showMapToast('Browser Anda tidak mendukung geolokasi.', 'error');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude.toFixed(6);
            const lng = pos.coords.longitude.toFixed(6);
            document.getElementById('editModalLatitude').value = lat;
            document.getElementById('editModalLongitude').value = lng;
            showMapToast('Koordinat posisi Anda saat ini berhasil diterapkan!', 'success');
        },
        err => {
            showMapToast('Gagal mendeteksi lokasi: ' + err.message, 'error');
        }
    );
}

function handleEditCoordinatesSubmit(event) {
    event.preventDefault();

    const customerId = document.getElementById('editCustomerId').value;
    const latStr = document.getElementById('editModalLatitude').value.trim();
    const lngStr = document.getElementById('editModalLongitude').value.trim();

    const lat = parseFloat(latStr);
    const lng = parseFloat(lngStr);

    if (isNaN(lat) || lat < -90 || lat > 90) {
        showMapToast('Latitude tidak valid. Masukkan angka antara -90 dan 90.', 'error');
        return;
    }

    if (isNaN(lng) || lng < -180 || lng > 180) {
        showMapToast('Longitude tidak valid. Masukkan angka antara -180 dan 180.', 'error');
        return;
    }

    const saveBtn = document.getElementById('saveEditCoordsBtn');
    const originalBtnHtml = saveBtn ? saveBtn.innerHTML : '';
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Menyimpan...</span>';
    }

    fetch(`/customers/${customerId}/coordinates`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            latitude: lat,
            longitude: lng
        })
    })
    .then(async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.message || 'Gagal menyimpan koordinat pelanggan.');
        }
        return data;
    })
    .then(data => {
        // Update client-side customer data
        const cust = customersDataMap.get(Number(customerId));
        if (cust) {
            cust.latitude = String(lat);
            cust.longitude = String(lng);
        }

        const marker = customerMarkersMap.get(Number(customerId));
        if (marker && markerClusterGroup) {
            // Remove from cluster, update coords and popup, re-add to cluster
            markerClusterGroup.removeLayer(marker);
            marker.setLatLng([lat, lng]);
            marker.setPopupContent(buildPopupContent(cust));
            markerClusterGroup.addLayer(marker);

            // Close modal
            closeEditModal();

            // Fly map to new coordinates
            map.flyTo([lat, lng], 18, { animate: true, duration: 1.2 });

            // Open popup at new position
            markerClusterGroup.zoomToShowLayer(marker, function() {
                marker.openPopup();
                const iconEl = marker.getElement();
                if (iconEl) {
                    const pinDiv = iconEl.querySelector('.custom-map-pin');
                    if (pinDiv) {
                        pinDiv.classList.add('marker-highlight-pulse');
                        setTimeout(() => pinDiv.classList.remove('marker-highlight-pulse'), 3600);
                    }
                }
            });
        } else {
            closeEditModal();
        }

        showMapToast(`Titik GPS untuk ${cust ? cust.name : 'pelanggan'} berhasil diperbarui!`, 'success');
    })
    .catch(error => {
        showMapToast(error.message, 'error');
    })
    .finally(() => {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });
}

// Toast notification helper
function showMapToast(message, type = 'success') {
    const existingToast = document.getElementById('mapCustomToast');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.id = 'mapCustomToast';
    toast.className = `fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-2xl border text-xs font-semibold transition-all transform duration-300 translate-y-2 opacity-0 ${
        type === 'success' 
            ? 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-500/20' 
            : 'bg-rose-600 text-white border-rose-500 shadow-rose-500/20'
    }`;
    toast.innerHTML = `
        <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'} text-base flex-shrink-0"></i>
        <span>${escapeHtml(message)}</span>
        <button type="button" onclick="this.parentElement.remove()" class="ml-2 text-white/80 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    }, 20);

    setTimeout(() => {
        toast.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// ==========================================
// MAP TOOLS (PUSATKAN, LOKASI SAYA, SATELIT)
// ==========================================
function centerMapOnMarkers() {
    if (!map) return;
    const validBounds = [];
    customersDataMap.forEach(cust => {
        let lat = parseFloat(cust.latitude);
        let lng = parseFloat(cust.longitude);
        if (lat > 6 && lat <= 141 && lng >= -11 && lng <= 6) {
            const temp = lat;
            lat = lng;
            lng = temp;
        }
        if (!isNaN(lat) && !isNaN(lng) && lat >= -11 && lat <= 6 && lng >= 95 && lng <= 141) {
            validBounds.push([lat, lng]);
        }
    });

    if (validBounds.length > 0) {
        map.fitBounds(validBounds, { padding: [40, 40], maxZoom: 15 });
    } else if (markerClusterGroup && markerClusterGroup.getBounds().isValid()) {
        map.fitBounds(markerClusterGroup.getBounds(), { padding: [40, 40], maxZoom: 15 });
    } else {
        map.setView([-6.175392, 106.827153], 12);
    }
}

function locateUserPosition() {
    if (!navigator.geolocation) {
        showMapToast('Browser Anda tidak mendukung geolokasi.', 'error');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        position => {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            if (map) {
                map.setView([userLat, userLng], 16);

                const userPin = L.divIcon({
                    className: 'custom-user-icon',
                    html: `<div style="width: 18px; height: 18px; background-color: #0284C7; border: 3px solid white; border-radius: 50%; box-shadow: 0 0 10px #0284C7;"></div>`,
                    iconSize: [18, 18],
                    iconAnchor: [9, 9]
                });

                L.marker([userLat, userLng], { icon: userPin })
                    .addTo(map)
                    .bindPopup('<div class="p-2 text-xs font-bold">Posisi Anda Saat Ini</div>')
                    .openPopup();
            }
        },
        err => {
            showMapToast('Tidak dapat mendeteksi lokasi: ' + err.message, 'error');
        }
    );
}

function toggleSatelliteLayer() {
    if (!map) return;
    const btn = document.getElementById('satelliteToggleBtn');
    const btnText = document.getElementById('layerNameText');

    if (currentLayerType === 'osm') {
        map.removeLayer(osmLayer);
        satelliteLayer.addTo(map);
        currentLayerType = 'satellite';
        if (btnText) btnText.textContent = 'Peta Jalan';
        if (btn) btn.classList.add('btn-active');
    } else {
        map.removeLayer(satelliteLayer);
        osmLayer.addTo(map);
        currentLayerType = 'osm';
        if (btnText) btnText.textContent = 'Satelit';
        if (btn) btn.classList.remove('btn-active');
    }
}

// Modal Tandai Koordinat Pelanggan (Belum Ditandai)
function openTandaiModalFromEl(btn) {
    if (!btn) return;
    openTandaiModal(
        btn.getAttribute('data-id'),
        btn.getAttribute('data-name'),
        btn.getAttribute('data-no-services'),
        btn.getAttribute('data-address')
    );
}

function openTandaiModal(id, name, noServices, address) {
    const modal = document.getElementById('tandaiModal');
    const form = document.getElementById('tandaiForm');
    
    if (modal && form) {
        form.action = '/customers/' + id + '/coordinates';
        document.getElementById('modalCustomerName').value = name;
        document.getElementById('modalNoServices').value = noServices;
        document.getElementById('modalCustomerAddress').value = address;
        document.getElementById('modalLatitude').value = '';
        document.getElementById('modalLongitude').value = '';
        modal.classList.remove('hidden');
    }
}

function closeTandaiModal() {
    const modal = document.getElementById('tandaiModal');
    if (modal) modal.classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBillingDropdown();
        closeTandaiModal();
        closeEditModal();
        const resultsContainer = document.getElementById('mapSearchResults');
        if (resultsContainer) resultsContainer.classList.add('hidden');
    }
});

// Clear lingering hash immediately if present
if (window.location.hash) {
    history.replaceState(null, '', window.location.pathname + window.location.search);
}

// Clear any saved scroll position
try {
    sessionStorage.removeItem('maps_scroll_pos');
} catch (e) {}

// Ensure page stays fixed at top (0,0)
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}
window.scrollTo(0, 0);
document.addEventListener('DOMContentLoaded', function() {
    window.scrollTo(0, 0);
});
</script>
@endpush
