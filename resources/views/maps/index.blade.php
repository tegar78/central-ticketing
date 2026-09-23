@extends('layouts.app')

@push('styles')
<!-- Leaflet CSS & MarkerCluster CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<style>
    /* Custom Marker styling */
    .custom-map-pin {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        border: 2px solid #ffffff;
        font-size: 11px;
    }
    .custom-map-pin.active { background-color: #10B981; }
    .custom-map-pin.isolated { background-color: #F59E0B; }
    .custom-map-pin.inactive { background-color: #EF4444; }
    .custom-map-pin.free { background-color: #0EA5E9; }

    /* Leaflet popup styling override */
    .leaflet-popup-content-wrapper {
        border-radius: 1rem;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    }
    .leaflet-popup-content {
        margin: 0 !important;
        line-height: 1.4;
    }
    /* Reset Leaflet link color overrides inside container and popup */
    .leaflet-container a {
        color: inherit;
        text-decoration: none;
    }
    .leaflet-popup-content a {
        color: inherit;
        text-decoration: none;
    }
    .leaflet-popup-content a.popup-wa-btn {
        background-color: #10B981 !important;
        color: #ffffff !important;
    }
    .leaflet-popup-content a.popup-wa-btn:hover {
        background-color: #059669 !important;
        color: #ffffff !important;
    }
    .leaflet-popup-content a.popup-wa-btn i,
    .leaflet-popup-content a.popup-wa-btn span {
        color: #ffffff !important;
    }
    .leaflet-popup-content a.popup-maps-btn {
        background-color: #f1f5f9;
        color: #334155 !important;
    }
    .leaflet-popup-content a.popup-maps-btn:hover {
        background-color: #e2e8f0;
        color: #0f172a !important;
    }
    .dark .leaflet-popup-content a.popup-maps-btn {
        background-color: #1e293b;
        color: #cbd5e1 !important;
    }
    .dark .leaflet-popup-content a.popup-maps-btn:hover {
        background-color: #334155;
        color: #ffffff !important;
    }
    .dark .leaflet-popup-content-wrapper {
        background-color: #0f172a;
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .dark .leaflet-popup-tip {
        background-color: #0f172a;
    }
    /* Marker cluster override */
    .marker-cluster-small {
        background-color: rgba(16, 185, 129, 0.3) !important;
    }
    .marker-cluster-small div {
        background-color: rgba(16, 185, 129, 0.9) !important;
        color: white !important;
        font-weight: bold;
    }
    .marker-cluster-medium {
        background-color: rgba(245, 158, 11, 0.3) !important;
    }
    .marker-cluster-medium div {
        background-color: rgba(245, 158, 11, 0.9) !important;
        color: white !important;
        font-weight: bold;
    }
    .marker-cluster-large {
        background-color: rgba(14, 165, 233, 0.3) !important;
    }
    .marker-cluster-large div {
        background-color: rgba(14, 165, 233, 0.9) !important;
        color: white !important;
        font-weight: bold;
    }
</style>
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
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Belum Ditandai: <strong class="font-bold">{{ number_format($unmarkedCount) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Filter & Map Controls Bar (billtest style) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 rounded-2xl shadow-sm space-y-3 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-4 transition-colors">
        <!-- Status & Server Filter -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Server Billing Selector -->
            <form method="GET" action="{{ route('maps.index') }}" class="inline-flex items-center">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <select name="billing_node_id" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200/70 dark:bg-slate-800 dark:hover:bg-slate-700/70 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer">
                    <option value="">Semua Server Billing</option>
                    @foreach($billingInstances as $b)
                        <option value="{{ $b->id }}" {{ request('billing_node_id') == $b->id ? 'selected' : '' }}>
                            [{{ $b->tenant_code }}] {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <span class="w-px h-5 bg-slate-200 dark:bg-slate-700 hidden sm:inline-block"></span>

            <!-- Status Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 mr-1 hidden md:inline uppercase tracking-wider">Status:</span>
                
                <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ !request('status') || request('status') === 'all' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Semua <span class="ml-1 opacity-80">({{ number_format($statusCounts['all']) }})</span>
                </a>

                <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'active'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'active' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Aktif <span class="ml-1 opacity-80">({{ number_format($statusCounts['active']) }})</span>
                </a>

                <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'isolated'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'isolated' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Isolir <span class="ml-1 opacity-80">({{ number_format($statusCounts['isolated']) }})</span>
                </a>

                <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'inactive'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'inactive' ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Non-Aktif <span class="ml-1 opacity-80">({{ number_format($statusCounts['inactive']) }})</span>
                </a>

                <a href="{{ route('maps.index', array_merge(request()->except(['status', 'unmarked_status', 'unmarked_search', 'unmarked_page']), ['status' => 'free'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'free' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Free <span class="ml-1 opacity-80">({{ number_format($statusCounts['free']) }})</span>
                </a>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <!-- Pusatkan Peta -->
            <button type="button" onclick="centerMapOnMarkers()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-crosshairs text-emerald-500"></i> Pusatkan
            </button>

            <!-- Lokasi Saya -->
            <button type="button" onclick="locateUserPosition()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-location-arrow text-teal-500"></i> Lokasi Saya
            </button>

            <!-- Toggle Layer Satelit -->
            <button type="button" onclick="toggleSatelliteLayer()" id="satelliteToggleBtn" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-layer-group text-sky-500"></i> <span id="layerNameText">Satelit</span>
            </button>

            <!-- Sync Data Button -->
            <form action="{{ route('customers.syncBilling') }}" method="POST" class="inline">
                @csrf
                @php
                    $syncTenant = request('billing_node_id') && isset($billingMap[request('billing_node_id')]) 
                        ? $billingMap[request('billing_node_id')]->tenant_code 
                        : 'all';
                @endphp
                <input type="hidden" name="tenant_code" value="{{ $syncTenant }}">
                <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold shadow-sm shadow-emerald-600/20 flex items-center gap-1.5 transition-all" title="Sinkronisasi data pelanggan dari server billing">
                    <i class="fa-solid fa-rotate"></i> Sync Tabel
                </button>
            </form>
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

    <!-- Bottom Section: Data Pelanggan yang Belum Ditandai Maps (billtest layout) -->
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
<div id="tandaiModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-5 space-y-4 transition-colors">
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3">
            <h3 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-map-pin text-emerald-500"></i> Tandai Titik GPS Pelanggan
            </h3>
            <button onclick="closeTandaiModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-400 flex items-center justify-center">
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
                <button type="button" onclick="closeTandaiModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-sm shadow-emerald-600/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Koordinat
                </button>
            </div>
        </form>
    </div>
</div>

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

document.addEventListener('DOMContentLoaded', function() {
    initCustomerMap();
});

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
    const customers = dataElement ? JSON.parse(dataElement.textContent || '[]') : [];

    const bMapElement = document.getElementById('billingMapData');
    const bMap = bMapElement ? JSON.parse(bMapElement.textContent || '{}') : {};

    const bounds = [];

    customers.forEach(cust => {
        const lat = parseFloat(cust.latitude);
        const lng = parseFloat(cust.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        bounds.push([lat, lng]);

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

        // Clean phone
        let phoneClean = cust.phone ? cust.phone.replace(/[^0-9]/g, '') : '';
        if (phoneClean && phoneClean.startsWith('0')) {
            phoneClean = '62' + phoneClean.substring(1);
        }

        // XSS sanitization helper
        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        const safeNoServices = escapeHtml(cust.no_services);
        const safeName = escapeHtml(cust.name);
        const safeAddress = escapeHtml(cust.address || '-');
        const safeStatus = escapeHtml(cust.status || 'Aktif');
        const safeOdp = escapeHtml(cust.odp_name || '');
        const tenantTag = cust.billing_node_id && bMap[cust.billing_node_id] 
            ? `[${escapeHtml(bMap[cust.billing_node_id].tenant_code)}]` 
            : '';

        const popupContent = `
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
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1.5">
                    ${phoneClean ? `
                    <a href="https://wa.me/${encodeURIComponent(phoneClean)}" target="_blank" class="popup-wa-btn flex-1 py-1.5 px-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-semibold flex items-center justify-center gap-1.5 text-center shadow-sm" style="color: #ffffff !important; background-color: #10B981 !important;">
                        <i class="fa-brands fa-whatsapp text-sm" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">WA</span>
                    </a>` : ''}
                    <a href="https://www.google.com/maps/place/${encodeURIComponent(lat)},${encodeURIComponent(lng)}" target="_blank" class="popup-maps-btn py-1.5 px-2.5 rounded-lg text-[11px] font-medium flex items-center justify-center gap-1.5 border border-slate-200 dark:border-slate-700">
                        <i class="fa-solid fa-location-arrow text-emerald-500"></i> <span>Maps</span>
                    </a>
                </div>
            </div>
        `;

        marker.bindPopup(popupContent);
        markerClusterGroup.addLayer(marker);
    });

    map.addLayer(markerClusterGroup);

    // If we have bounds, fit map to all markers
    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
    }
}

function centerMapOnMarkers() {
    if (!map || !markerClusterGroup) return;
    const bounds = markerClusterGroup.getBounds();
    if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
    }
}

function locateUserPosition() {
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung geolokasi.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        position => {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            if (map) {
                map.setView([userLat, userLng], 16);

                // Add temporary user location marker
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
            alert('Tidak dapat mendeteksi lokasi: ' + err.message);
        }
    );
}

function toggleSatelliteLayer() {
    if (!map) return;
    const btnText = document.getElementById('layerNameText');

    if (currentLayerType === 'osm') {
        map.removeLayer(osmLayer);
        satelliteLayer.addTo(map);
        currentLayerType = 'satellite';
        if (btnText) btnText.textContent = 'Peta Jalan';
    } else {
        map.removeLayer(satelliteLayer);
        osmLayer.addTo(map);
        currentLayerType = 'osm';
        if (btnText) btnText.textContent = 'Satelit';
    }
}

// Modal Tandai Koordinat Pelanggan
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
        closeTandaiModal();
    }
});

// Clear lingering hash immediately if present (e.g. #map-section from previous navigation)
if (window.location.hash) {
    history.replaceState(null, '', window.location.pathname + window.location.search);
}

// Clear any saved scroll position
try {
    sessionStorage.removeItem('maps_scroll_pos');
} catch (e) {}

// Ensure page stays fixed at top (0,0) so top navbar, header, and maps are completely visible without scrolling
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}
window.scrollTo(0, 0);
document.addEventListener('DOMContentLoaded', function() {
    window.scrollTo(0, 0);
});
</script>
@endpush
