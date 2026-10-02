@extends('layouts.app')

@section('title', 'Maps ODP & Infrastruktur Jaringan')

@push('styles')
<!-- Leaflet CSS & MarkerCluster CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<link rel="stylesheet" href="{{ asset('css/maps.css') }}">
<style>
    /* ODP Pin Styles */
    .custom-odp-pin {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
        border: 2px solid #ffffff;
        font-size: 11px;
        transition: transform 0.2s ease;
    }
    .custom-odp-pin:hover {
        transform: scale(1.15);
    }
    .custom-odp-pin.active { background-color: #10B981; }
    .custom-odp-pin.full { background-color: #F59E0B; }
    .custom-odp-pin.maintenance { background-color: #0284C7; }
    .custom-odp-pin.damaged { background-color: #EF4444; }

    /* Map Click Picking Crosshair Cursor */
    .map-picking-mode {
        cursor: crosshair !important;
    }

    /* Modal Backdrop Transitions */
    .modal-backdrop {
        background-color: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
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
                    <i class="fa-solid fa-network-wired text-base"></i>
                </div>
                <span>Maps ODP (Optical Distribution Point)</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Visualisasi persebaran kotak ODP fiber optik, kapasitas port splitter, dan dokumentasi foto fisik di lapangan.
            </p>
        </div>
        <!-- Right Stat Badges -->
        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Total ODP: <strong class="font-bold">{{ number_format($totalOdps) }}</strong></span>
            </div>
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/40 text-xs font-semibold">
                <i class="fa-solid fa-plug text-[10px]"></i>
                <span>Port: <strong>{{ number_format($totalUsedPorts) }}/{{ number_format($totalCapacity) }}</strong> ({{ $overallPortOccupancy }}%)</span>
            </div>
        </div>
    </div>

    <!-- Filter & Map Controls Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 rounded-2xl shadow-sm space-y-3 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-4 transition-colors">
        <!-- Status & Server Filter -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Server Billing Selector -->
            <form method="GET" action="{{ route('odp.index') }}" class="inline-flex items-center">
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
                
                <a href="{{ route('odp.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ !request('status') || request('status') === 'all' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Semua <span class="ml-1 opacity-80">({{ number_format($statusCounts['all']) }})</span>
                </a>

                <a href="{{ route('odp.index', array_merge(request()->except(['status', 'page']), ['status' => 'active'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'active' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Tersedia <span class="ml-1 opacity-80">({{ number_format($statusCounts['active']) }})</span>
                </a>

                <a href="{{ route('odp.index', array_merge(request()->except(['status', 'page']), ['status' => 'full'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'full' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Penuh <span class="ml-1 opacity-80">({{ number_format($statusCounts['full']) }})</span>
                </a>

                <a href="{{ route('odp.index', array_merge(request()->except(['status', 'page']), ['status' => 'maintenance'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'maintenance' ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Maintenance <span class="ml-1 opacity-80">({{ number_format($statusCounts['maintenance']) }})</span>
                </a>

                <a href="{{ route('odp.index', array_merge(request()->except(['status', 'page']), ['status' => 'damaged'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === 'damaged' ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Gangguan <span class="ml-1 opacity-80">({{ number_format($statusCounts['damaged']) }})</span>
                </a>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <!-- Pusatkan Peta -->
            <button type="button" onclick="centerOdpMapOnMarkers()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-all" title="Pusatkan Peta ke Sebaran ODP">
                <i class="fa-solid fa-crosshairs text-emerald-500"></i> Pusatkan
            </button>

            <!-- Lokasi Saya -->
            <button type="button" onclick="locateUserPositionOdp()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-all" title="Deteksi Lokasi GPS Saya">
                <i class="fa-solid fa-location-arrow text-teal-500"></i> Lokasi Saya
            </button>

            <!-- Toggle Layer Satelit -->
            <button type="button" onclick="toggleOdpSatelliteLayer()" id="satelliteToggleBtnOdp" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-layer-group text-sky-500"></i> <span id="layerNameTextOdp">Satelit</span>
            </button>

            @if(in_array($user->role, ['admin', 'operator']))
            <!-- Tambah ODP Baru Modal Trigger -->
            <button type="button" onclick="openAddOdpModal()" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-sm shadow-emerald-600/20 flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-plus"></i> Tambah ODP
            </button>
            @endif
        </div>
    </div>

    <!-- Active Picking Mode Notification Banner (Hidden by default) -->
    <div id="pickingNotification" class="hidden p-3 bg-amber-500 text-white rounded-xl shadow-lg flex items-center justify-between transition-all animate-pulse">
        <div class="flex items-center gap-2.5 text-xs font-bold">
            <i class="fa-solid fa-crosshairs text-base"></i>
            <span>Mode Pilih Titik Aktif: Klik pada peta di lokasi fisik ODP untuk mengisi koordinat secara otomatis!</span>
        </div>
        <button type="button" onclick="cancelMapPicking()" class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-semibold">
            Batal
        </button>
    </div>

    <!-- Map Container -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden relative">
        <div id="odpMap" class="w-full h-[520px] sm:h-[620px] z-10"></div>
        
        <!-- Floating Legend on Map -->
        <div class="absolute bottom-4 left-4 z-20 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-md text-[11px] space-y-1.5">
            <div class="font-bold text-slate-800 dark:text-slate-200 mb-1 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-info text-emerald-500"></i> Status ODP:
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-3 h-3 rounded-md bg-emerald-500 flex items-center justify-center text-white text-[8px]"><i class="fa-solid fa-check"></i></span>
                <span>Tersedia (Port Kosong)</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-3 h-3 rounded-md bg-amber-500 flex items-center justify-center text-white text-[8px]"><i class="fa-solid fa-plug"></i></span>
                <span>Penuh (Full Port)</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-3 h-3 rounded-md bg-sky-600 flex items-center justify-center text-white text-[8px]"><i class="fa-solid fa-wrench"></i></span>
                <span>Maintenance</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                <span class="w-3 h-3 rounded-md bg-rose-500 flex items-center justify-center text-white text-[8px]"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span>Gangguan / Rusak</span>
            </div>
        </div>
    </div>

    <!-- ODP Table & Search Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-colors">
        <!-- Card Header -->
        <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                        <span>Daftar Master Data ODP</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40">
                            {{ $odpList->total() }} ODP
                        </span>
                    </h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                        Kelola data titik ODP, kapasitas port splitter, dan update dokumentasi foto fisik ODP.
                    </p>
                </div>
            </div>

            <!-- Search & Sort Form -->
            <form action="{{ route('odp.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('billing_node_id'))
                    <input type="hidden" name="billing_node_id" value="{{ request('billing_node_id') }}">
                @endif

                <!-- Sorting Dropdown (Nama ODP Ascending & Descending) -->
                <div class="relative">
                    <select name="sort" onchange="this.form.submit()" class="pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer">
                        <option value="name_asc" {{ ($sortBy === 'name' && $sortDir === 'asc') ? 'selected' : '' }}>
                            Nama ODP: A → Z (Ascending)
                        </option>
                        <option value="name_desc" {{ ($sortBy === 'name' && $sortDir === 'desc') ? 'selected' : '' }}>
                            Nama ODP: Z → A (Descending)
                        </option>
                        <option value="code_odp_asc" {{ ($sortBy === 'code_odp' && $sortDir === 'asc') ? 'selected' : '' }}>
                            Kode ODP: A → Z (Ascending)
                        </option>
                        <option value="code_odp_desc" {{ ($sortBy === 'code_odp' && $sortDir === 'desc') ? 'selected' : '' }}>
                            Kode ODP: Z → A (Descending)
                        </option>
                        <option value="updated_at_desc" {{ ($sortBy === 'updated_at') ? 'selected' : '' }}>
                            Terbaru Diperbarui
                        </option>
                    </select>
                    <i class="fa-solid fa-arrow-down-short-wide absolute left-2.5 top-2.5 text-slate-400 text-xs pointer-events-none"></i>
                </div>

                <div class="relative w-full sm:w-60">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode ODP, alamat, nama..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                </div>
                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold">
                    Cari
                </button>
                @if(request('search') || request('sort') || request('sort_by'))
                <a href="{{ route('odp.index', request()->only(['status', 'billing_node_id'])) }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs" title="Reset pencarian dan urutan">
                    Reset
                </a>
                @endif
            </form>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">No</th>
                        <th class="px-4 py-3">Foto</th>
                        <th class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('odp.index', array_merge(request()->query(), [
                                    'sort_by' => 'name',
                                    'sort_dir' => ($sortBy === 'name' && $sortDir === 'asc') ? 'desc' : 'asc'
                                ])) }}" class="inline-flex items-center gap-1 font-bold transition-colors {{ $sortBy === 'name' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}" title="Urutkan berdasarkan Nama ODP (Klik untuk toggle Ascending / Descending)">
                                    <span>Nama & Kode ODP</span>
                                    @if($sortBy === 'name')
                                        <i class="fa-solid fa-arrow-{{ $sortDir === 'asc' ? 'up-a-z' : 'down-z-a' }} text-xs text-emerald-600 dark:text-emerald-400"></i>
                                    @elseif($sortBy === 'code_odp')
                                        <i class="fa-solid fa-arrow-{{ $sortDir === 'asc' ? 'up-1-9' : 'down-9-1' }} text-xs text-sky-600 dark:text-sky-400" title="Sedang diurutkan berdasarkan Kode ODP"></i>
                                    @else
                                        <i class="fa-solid fa-sort text-[10px] text-slate-400 opacity-60"></i>
                                    @endif
                                </a>
                                <a href="{{ route('odp.index', array_merge(request()->query(), [
                                    'sort_by' => 'code_odp',
                                    'sort_dir' => ($sortBy === 'code_odp' && $sortDir === 'asc') ? 'desc' : 'asc'
                                ])) }}" class="px-1.5 py-0.5 rounded text-[9px] font-mono border transition-all {{ $sortBy === 'code_odp' ? 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 hover:border-slate-300' }}" title="Sort Kode ODP ({{ $sortBy === 'code_odp' ? strtoupper($sortDir) : 'A-Z' }})">
                                    Kode {{ $sortBy === 'code_odp' ? ($sortDir === 'asc' ? '↑' : '↓') : '↕' }}
                                </a>
                            </div>
                        </th>
                        <th class="px-4 py-3">Server Billing</th>
                        <th class="px-4 py-3">Kapasitas Port</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Pelanggan Terhubung</th>
                        <th class="px-4 py-3">Koordinat GPS</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800">
                    @forelse($odpList as $index => $odp)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-4 py-3 text-center font-mono text-slate-400 text-[11px]">
                            {{ $odpList->firstItem() + $index }}
                        </td>
                        <td class="px-4 py-3">
                            @if($odp->photo_url)
                                <div class="relative group cursor-pointer w-11 h-11 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm" onclick="openLightbox('{{ $odp->photo_url }}', '{{ $odp->code_odp }}')">
                                    <img src="{{ $odp->photo_url }}" alt="{{ $odp->code_odp }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs transition-opacity">
                                        <i class="fa-solid fa-expand"></i>
                                    </div>
                                </div>
                            @else
                                <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex flex-col items-center justify-center text-slate-400 text-[10px] cursor-pointer hover:bg-slate-200/60 transition-colors" onclick="openUploadPhotoModal('{{ $odp->id }}', '{{ $odp->code_odp }}')" title="Klik untuk upload foto ODP">
                                    <i class="fa-solid fa-camera mb-0.5 text-xs"></i>
                                    <span>Foto</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <i class="fa-solid fa-network-wired text-emerald-600 dark:text-emerald-400 text-xs"></i>
                                <span>{{ $odp->code_odp }}</span>
                            </div>
                            @if($odp->name)
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $odp->name }}</div>
                            @endif
                            @if($odp->address)
                                <div class="text-[10px] text-slate-400 truncate max-w-xs"><i class="fa-solid fa-location-dot text-[9px] mr-1"></i>{{ $odp->address }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($odp->billingNode)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    [{{ $odp->billingNode->tenant_code }}] {{ $odp->billingNode->name }}
                                </span>
                            @else
                                <span class="text-slate-400 text-[11px]">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-36">
                                <div class="flex items-center justify-between text-[11px] mb-1 font-semibold">
                                    <span>{{ $odp->used_ports }}/{{ $odp->total_ports }} Port</span>
                                    <span class="{{ $odp->occupancy_percentage >= 100 ? 'text-amber-600' : 'text-slate-500' }}">{{ $odp->occupancy_percentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                    <div class="odp-progress-bar h-full rounded-full {{ $odp->occupancy_percentage >= 100 ? 'bg-amber-500' : ($odp->occupancy_percentage > 70 ? 'bg-teal-500' : 'bg-emerald-500') }}" data-percent="{{ min(100, (int)$odp->occupancy_percentage) }}"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($odp->status === 'active')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40">
                                    Tersedia
                                </span>
                            @elseif($odp->status === 'full')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40">
                                    Penuh
                                </span>
                            @elseif($odp->status === 'maintenance')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/40">
                                    Maintenance
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40">
                                    Gangguan
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium">
                            <button type="button" onclick="openOdpPortsModal('{{ $odp->id }}', '{{ $odp->code_odp }}')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-950/40 text-slate-700 hover:text-indigo-700 dark:text-slate-300 dark:hover:text-indigo-300 text-[11px] font-semibold transition-all border border-transparent hover:border-indigo-300 dark:hover:border-indigo-800 cursor-pointer" title="Kelola Pelanggan & Port ODP">
                                <i class="fa-solid fa-users text-emerald-500 text-[10px]"></i>
                                <span>{{ $odp->customers_count }} Pelanggan</span>
                            </button>
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                            @php
                                $rawLat = trim(trim((string)$odp->latitude), '"\'');
                                $rawLng = trim(trim((string)$odp->longitude), '"\'');
                                $cleanLat = filter_var(rtrim($rawLat, ','), FILTER_VALIDATE_FLOAT);
                                $cleanLng = filter_var(rtrim($rawLng, ','), FILTER_VALIDATE_FLOAT);
                            @endphp
                            @if($cleanLat !== false && $cleanLng !== false && ($cleanLat != 0 || $cleanLng != 0))
                                <div class="flex items-center gap-1.5">
                                    <span>{{ round($cleanLat, 5) }}, {{ round($cleanLng, 5) }}</span>
                                    <a href="https://www.google.com/maps?q={{ $cleanLat }},{{ $cleanLng }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:text-emerald-500 text-xs" title="Buka di Google Maps">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </div>
                            @elseif(!empty($odp->latitude) && !empty($odp->longitude) && $odp->latitude !== '0' && $odp->longitude !== '0')
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate max-w-[130px]" title="{{ $odp->latitude }}, {{ $odp->longitude }}">{{ $odp->latitude }}, {{ $odp->longitude }}</span>
                                    <a href="https://www.google.com/maps?q={{ urlencode($odp->latitude . ',' . $odp->longitude) }}" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:text-emerald-500 text-xs" title="Buka di Google Maps">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </div>
                            @else
                                <span class="text-rose-400 text-[11px] font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <!-- Daftar Port & Pelanggan Terpasang -->
                                <button type="button" onclick="openOdpPortsModal('{{ $odp->id }}', '{{ $odp->code_odp }}')" class="w-7 h-7 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/50 dark:text-indigo-400 flex items-center justify-center transition-colors" title="Daftar Pelanggan Terpasang di Port">
                                    <i class="fa-solid fa-plug text-xs"></i>
                                </button>

                                <!-- Fly to map button -->
                                @if($cleanLat !== false && $cleanLng !== false && ($cleanLat != 0 || $cleanLng != 0))
                                <button type="button" onclick="flyToOdpCoordinates('{{ $cleanLat }}', '{{ $cleanLng }}', '{{ $odp->code_odp }}')" class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 dark:text-emerald-400 flex items-center justify-center transition-colors" title="Lihat di Peta">
                                    <i class="fa-solid fa-location-crosshairs text-xs"></i>
                                </button>
                                @endif

                                <!-- Upload / Change Photo -->
                                <button type="button" onclick="openUploadPhotoModal('{{ $odp->id }}', '{{ $odp->code_odp }}')" class="w-7 h-7 rounded-lg bg-sky-50 hover:bg-sky-100 text-sky-600 dark:bg-sky-950/40 dark:hover:bg-sky-900/50 dark:text-sky-400 flex items-center justify-center transition-colors" title="Unggah / Ganti Foto ODP">
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </button>

                                @if(in_array($user->role, ['admin', 'operator']))
                                <!-- Edit ODP -->
                                <button type="button" data-odp="{{ base64_encode(json_encode($odp)) }}" onclick="openEditOdpModal(this)" class="w-7 h-7 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 dark:bg-amber-950/40 dark:hover:bg-amber-900/50 dark:text-amber-400 flex items-center justify-center transition-colors" title="Edit Data ODP">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                @endif

                                @if($user->role === 'admin')
                                <!-- Delete ODP -->
                                <form action="{{ route('odp.destroy', $odp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ODP {{ $odp->code_odp }}? Foto dan titik koordinat akan terhapus.')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 dark:text-rose-400 flex items-center justify-center transition-colors" title="Hapus ODP">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                            <i class="fa-solid fa-network-wired text-3xl mb-2 block opacity-40"></i>
                            <span>Belum ada data ODP yang tersimpan. Klik tombol <strong>Tambah ODP</strong> untuk menambahkan data.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <script>
                document.querySelectorAll('.odp-progress-bar').forEach(function(el) {
                    el.style.width = el.getAttribute('data-percent') + '%';
                });
            </script>
        </div>

        @if($odpList->hasPages())
        <div class="p-4 border-t border-slate-200/80 dark:border-slate-800">
            {{ $odpList->links() }}
        </div>
        @endif
    </div>
</div>

{{-- MODAL 1: Tambah ODP Baru --}}
@if(in_array($user->role, ['admin', 'operator']))
<div id="addOdpModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 overflow-y-auto modal-backdrop">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl relative my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-slate-800">
            <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-emerald-600"></i>
                <span>Tambah Data ODP Baru</span>
            </h3>
            <button type="button" onclick="closeAddOdpModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('odp.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Kode ODP *</label>
                    <input type="text" name="code_odp" required placeholder="Contoh: ODP-TNG-W6-01" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Server Billing</label>
                    <select name="billing_node_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                        <option value="">Pilih Server (Opsional)</option>
                        @foreach($billingInstances as $b)
                            <option value="{{ $b->id }}">[{{ $b->tenant_code }}] {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama / Deskripsi ODP</label>
                <input type="text" name="name" placeholder="Contoh: ODP Depan Masjid Nurul Huda" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
            </div>

            <!-- Koordinat dengan fitur pilih titik di peta -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Koordinat GPS *</label>
                    <button type="button" onclick="startPickingCoordinatesOnMap('add')" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 flex items-center gap-1">
                        <i class="fa-solid fa-map-pin"></i> Klik Titik di Peta
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="latitude" id="addOdpLat" required placeholder="Latitude (contoh: -6.123456)" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    <input type="text" name="longitude" id="addOdpLng" required placeholder="Longitude (contoh: 106.123456)" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Kapasitas Port *</label>
                    <input type="number" name="total_ports" value="16" min="1" max="256" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Port Terpakai</label>
                    <input type="number" name="used_ports" value="0" min="0" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status ODP *</label>
                    <select name="status" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                        <option value="active">Tersedia (Active)</option>
                        <option value="full">Penuh (Full)</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="damaged">Gangguan / Rusak</option>
                    </select>
                </div>
            </div>

            <!-- Upload Foto ODP -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Foto Fisik ODP (Maks 5MB)</label>
                <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center hover:border-emerald-500 transition-colors">
                    <input type="file" name="photo" id="addOdpPhotoInput" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewOdpImage(this, 'addPhotoPreview')" class="hidden">
                    <label for="addOdpPhotoInput" class="cursor-pointer flex flex-col items-center justify-center">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400 mb-1"></i>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Pilih Berkas Foto atau Tarik ke Sini</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">Mendukung format JPG, PNG, WEBP hingga 5MB</span>
                    </label>
                    <div id="addPhotoPreview" class="hidden mt-3 max-h-40 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                        <img src="" alt="Preview Foto" class="w-full h-auto object-cover max-h-40">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Alamat / Patokan Tiang</label>
                <textarea name="address" rows="2" placeholder="Contoh: Jl. Ki Hajar Dewantara No. 12, Tiang Telkom sebelah pohon beringin" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Catatan Teknis / Redaman</label>
                <input type="text" name="notes" placeholder="Contoh: Redaman -19.5 dBm, Splitter 1:8" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="closeAddOdpModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-sm shadow-emerald-600/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan ODP ke MariaDB
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Edit Data ODP --}}
<div id="editOdpModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 overflow-y-auto modal-backdrop">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl relative my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-slate-800">
            <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-500"></i>
                <span>Edit Data ODP: <span id="editModalCodeHeader" class="font-mono text-emerald-600"></span></span>
            </h3>
            <button type="button" onclick="closeEditOdpModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="editOdpForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Kode ODP *</label>
                    <input type="text" name="code_odp" id="editCodeOdp" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Server Billing</label>
                    <select name="billing_node_id" id="editBillingNodeId" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                        <option value="">Pilih Server</option>
                        @foreach($billingInstances as $b)
                            <option value="{{ $b->id }}">[{{ $b->tenant_code }}] {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama / Deskripsi ODP</label>
                <input type="text" name="name" id="editName" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Koordinat GPS *</label>
                    <button type="button" onclick="startPickingCoordinatesOnMap('edit')" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 flex items-center gap-1">
                        <i class="fa-solid fa-map-pin"></i> Klik Titik di Peta
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="latitude" id="editLatitude" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    <input type="text" name="longitude" id="editLongitude" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Kapasitas Port *</label>
                    <input type="number" name="total_ports" id="editTotalPorts" min="1" max="256" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Port Terpakai</label>
                    <input type="number" name="used_ports" id="editUsedPorts" min="0" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status ODP *</label>
                    <select name="status" id="editStatus" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                        <option value="active">Tersedia (Active)</option>
                        <option value="full">Penuh (Full)</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="damaged">Gangguan / Rusak</option>
                    </select>
                </div>
            </div>

            <!-- Ganti Foto ODP -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Perbarui Foto Fisik ODP</label>
                <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center hover:border-emerald-500 transition-colors">
                    <input type="file" name="photo" id="editOdpPhotoInput" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewOdpImage(this, 'editPhotoPreview')" class="hidden">
                    <label for="editOdpPhotoInput" class="cursor-pointer flex flex-col items-center justify-center">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400 mb-1"></i>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Pilih Berkas Foto Baru</span>
                    </label>
                    <div id="editPhotoPreview" class="hidden mt-3 max-h-40 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                        <img src="" alt="Preview Foto" class="w-full h-auto object-cover max-h-40">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Alamat / Patokan Tiang</label>
                <textarea name="address" id="editAddress" rows="2" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Catatan Teknis / Redaman</label>
                <input type="text" name="notes" id="editNotes" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="closeEditOdpModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold shadow-sm shadow-amber-600/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- MODAL 3: Upload Foto ODP Cepat (Akses Teknisi, Operator, Admin) --}}
<div id="uploadPhotoModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 overflow-y-auto modal-backdrop">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-slate-800">
            <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                <i class="fa-solid fa-camera text-sky-500"></i>
                <span>Unggah Foto ODP: <span id="uploadPhotoCodeHeader" class="font-mono text-emerald-600"></span></span>
            </h3>
            <button type="button" onclick="closeUploadPhotoModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="uploadPhotoForm" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Ambil Foto Fisik Kotak ODP</label>
                <div class="border-2 border-dashed border-sky-300 dark:border-sky-800 rounded-2xl p-6 text-center hover:bg-sky-50/50 dark:hover:bg-sky-950/20 transition-all">
                    <input type="file" name="photo" id="quickPhotoInput" accept="image/*" capture="environment" required onchange="previewOdpImage(this, 'quickPhotoPreview')" class="hidden">
                    <label for="quickPhotoInput" class="cursor-pointer flex flex-col items-center justify-center">
                        <div class="w-14 h-14 rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400 flex items-center justify-center text-2xl mb-2">
                            <i class="fa-solid fa-camera-retro"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Klik untuk Buka Kamera / Pilih Berkas</span>
                        <span class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WEBP (Maksimal 5MB)</span>
                    </label>
                    <div id="quickPhotoPreview" class="hidden mt-3 max-h-56 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                        <img src="" alt="Preview Foto" class="w-full h-auto object-cover max-h-56">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200/80 dark:border-slate-800">
                <button type="button" onclick="closeUploadPhotoModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-sm shadow-sky-600/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Simpan Foto ODP
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 4: Fullscreen Lightbox Foto ODP --}}
<div id="odpLightboxModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/85 backdrop-blur-md" onclick="closeLightbox()">
    <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center" onclick="event.stopPropagation()">
        <button type="button" onclick="closeLightbox()" class="absolute -top-10 right-0 text-white hover:text-rose-400 text-xl font-bold transition-colors">
            <i class="fa-solid fa-xmark"></i> Tutup
        </button>
        <img id="lightboxImage" src="" alt="Foto ODP" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain border border-white/20">
        <div class="mt-3 text-center text-white">
            <div id="lightboxCaption" class="text-sm font-bold font-mono"></div>
            <a id="lightboxDownloadBtn" href="" download class="inline-flex items-center gap-1 text-xs text-emerald-400 hover:underline mt-1">
                <i class="fa-solid fa-download"></i> Unduh Foto Asli
            </a>
        </div>
    </div>
</div>

{{-- MODAL 5: Daftar Pelanggan yang Terpasang di ODP (Port Allocation Matrix) --}}
<div id="odpPortsModal" data-can-edit="{{ in_array($user->role, ['admin', 'operator']) ? 'true' : 'false' }}" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 overflow-y-auto modal-backdrop">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-3xl w-full p-6 shadow-2xl relative my-8 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-slate-800 flex-shrink-0">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-base sm:text-lg flex items-center gap-2">
                    <i class="fa-solid fa-network-wired text-indigo-500"></i>
                    <span>Daftar Pelanggan yang terpasang</span>
                </h3>
                <div class="flex items-center gap-2 mt-1 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
                    <span id="portsModalOdpCode" class="font-bold font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800/40"></span>
                    <span id="portsModalBilling" class="font-semibold text-slate-600 dark:text-slate-300"></span>
                    <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                    <span id="portsModalCapacity" class="font-semibold text-indigo-600 dark:text-indigo-400"></span>
                </div>
            </div>
            <button type="button" onclick="closeOdpPortsModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Role Notice for Technician -->
        <div id="portsModalRoleNotice" class="hidden mt-3 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-amber-800 dark:text-amber-300 text-xs flex items-center gap-2">
            <i class="fa-solid fa-shield-halved text-amber-500"></i>
            <span>Mode Baca (Teknisi): Anda hanya memiliki hak akses untuk melihat daftar port dan status pelanggan.</span>
        </div>

        <!-- Loading Spinner -->
        <div id="portsModalLoading" class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-500 mb-2"></i>
            <p class="text-xs">Memuat data port dan pelanggan...</p>
        </div>

        <!-- Scrollable Table Container -->
        <div id="portsModalContent" class="hidden mt-4 overflow-y-auto flex-1 border border-slate-200 dark:border-slate-800 rounded-xl">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 sticky top-0 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-4 w-20 text-center">No Port</th>
                        <th class="py-3 px-4">Nama Pelanggan</th>
                        <th class="py-3 px-4 w-32 text-center">Status</th>
                        <th class="py-3 px-4 w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="portsTableBody" class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    <!-- Dynamic Rows populated by JS -->
                </tbody>
            </table>
        </div>

        <!-- Unassigned Customers Warning (if any) -->
        <div id="unassignedCustomersSection" class="hidden mt-3 p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 rounded-xl text-xs">
            <div class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5 mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Pelanggan terhubung ke ODP ini tetapi belum memiliki nomor port:</span>
            </div>
            <div id="unassignedCustomersList" class="flex flex-wrap gap-1.5 mt-1.5"></div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-4 mt-3 border-t border-slate-200/80 dark:border-slate-800 flex-shrink-0">
            <div class="text-[11px] text-slate-400">
                <i class="fa-solid fa-rotate mr-1"></i>Status pelanggan tersinkron otomatis dari Node Billing.
            </div>
            <button type="button" onclick="closeOdpPortsModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- SUB-MODAL: Assign / Pasang Pelanggan ke Port --}}
<div id="assignPortModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 overflow-y-auto modal-backdrop">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative my-8">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 dark:border-slate-800">
            <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                <i class="fa-solid fa-plug text-emerald-600"></i>
                <span>Pasang Pelanggan ke <span id="assignModalPortTitle" class="font-mono text-emerald-600">Port #</span></span>
            </h3>
            <button type="button" onclick="closeAssignPortModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="mt-4 space-y-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Cari Pelanggan di Server Billing Terkait</label>
                <div class="relative">
                    <input type="text" id="assignSearchInput" oninput="debounceSearchCustomers()" placeholder="Ketik nama, no layanan, atau telepon..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- List Results -->
            <div id="assignSearchResultsList" class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50/50 dark:bg-slate-800/30">
                <div class="p-4 text-center text-xs text-slate-400">
                    Memuat daftar pelanggan...
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 mt-3 border-t border-slate-200/80 dark:border-slate-800">
            <button type="button" onclick="closeAssignPortModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- Raw ODP JSON Data for Leaflet -->
<script type="application/json" id="odpsData">
    @json($markedOdps)
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
let odpMap = null;
let odpMarkerClusterGroup = null;
let odpOsmLayer = null;
let odpSatelliteLayer = null;
let currentOdpLayerType = 'osm';

// Picking state
let isPickingMode = false;
let pickingTarget = 'add'; // 'add' or 'edit'
let pickingTempMarker = null;

document.addEventListener('DOMContentLoaded', function() {
    initOdpMap();
});

function initOdpMap() {
    const container = document.getElementById('odpMap');
    if (!container || typeof L === 'undefined') return;

    const defaultLat = -6.175392;
    const defaultLng = 106.827153;
    const defaultZoom = 12;

    odpMap = L.map('odpMap', {
        zoomControl: true,
        attributionControl: true
    }).setView([defaultLat, defaultLng], defaultZoom);

    odpOsmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(odpMap);

    odpSatelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: '&copy; Esri &mdash; Satellite'
    });

    odpMarkerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
    });

    // Parse ODP data
    const dataEl = document.getElementById('odpsData');
    const odps = dataEl ? JSON.parse(dataEl.textContent || '[]') : [];

    const bMapEl = document.getElementById('billingMapData');
    const bMap = bMapEl ? JSON.parse(bMapEl.textContent || '{}') : {};

    const bounds = [];

    odps.forEach(odp => {
        const lat = parseFloat(odp.latitude);
        const lng = parseFloat(odp.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        bounds.push([lat, lng]);

        const statusClass = (odp.status || 'active').toLowerCase();

        // Custom ODP pin with network-wired icon
        const pinIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div class="custom-odp-pin ${statusClass}" style="width: 28px; height: 28px;"><i class="fa-solid fa-network-wired text-[11px]"></i></div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14],
            popupAnchor: [0, -16]
        });

        const marker = L.marker([lat, lng], { icon: pinIcon });

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        const safeCode = escapeHtml(odp.code_odp);
        const safeName = escapeHtml(odp.name || '-');
        const safeAddress = escapeHtml(odp.address || '-');
        const safeNotes = escapeHtml(odp.notes || '-');
        const totalPorts = odp.total_ports || 16;
        const usedPorts = odp.used_ports || 0;
        const availablePorts = Math.max(0, totalPorts - usedPorts);
        const occupancy = totalPorts > 0 ? Math.min(100, Math.round((usedPorts / totalPorts) * 100)) : 0;
        const custCount = odp.customers_count || 0;

        let tenantName = 'Lokal';
        if (odp.billing_node_id && bMap[odp.billing_node_id]) {
            tenantName = escapeHtml(bMap[odp.billing_node_id].name || bMap[odp.billing_node_id].tenant_code);
        }

        // Photo thumbnail or placeholder
        let photoMarkup = '';
        if (odp.photo_url) {
            photoMarkup = `
                <div class="relative w-full h-32 rounded-xl overflow-hidden mb-2.5 bg-slate-900 cursor-pointer group" onclick="openLightbox('${odp.photo_url}', '${safeCode}')">
                    <img src="${odp.photo_url}" alt="${safeCode}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                    <div class="absolute inset-0 bg-black/30 group-hover:bg-black/10 transition-colors flex items-center justify-center text-white text-xs font-bold gap-1.5 opacity-90">
                        <i class="fa-solid fa-expand"></i> <span>Perbesar Foto</span>
                    </div>
                </div>
            `;
        } else {
            photoMarkup = `
                <div class="w-full py-2.5 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-between mb-2.5 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-camera"></i> Belum ada foto fisik</span>
                    <button type="button" onclick="openUploadPhotoModal(${odp.id}, '${safeCode}')" class="px-2 py-0.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-[10px] font-bold">
                        Upload
                    </button>
                </div>
            `;
        }

        const popupContent = `
            <div class="p-4 w-72 text-slate-800 dark:text-slate-100">
                ${photoMarkup}
                <div class="flex items-start justify-between gap-2 mb-1.5">
                    <div>
                        <div class="font-black text-sm font-mono text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <i class="fa-solid fa-network-wired text-xs"></i> ${safeCode}
                        </div>
                        <div class="text-[11px] font-semibold text-slate-700 dark:text-slate-300">${safeName}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider ${
                        odp.status === 'active' ? 'bg-emerald-100 text-emerald-800' :
                        (odp.status === 'full' ? 'bg-amber-100 text-amber-800' : 
                        (odp.status === 'maintenance' ? 'bg-sky-100 text-sky-800' : 'bg-rose-100 text-rose-800'))
                    }">
                        ${odp.status}
                    </span>
                </div>

                <div class="text-[10px] text-slate-400 mb-2">
                    <i class="fa-solid fa-server mr-1"></i>Server: <strong class="text-slate-600 dark:text-slate-300">${tenantName}</strong>
                </div>

                <!-- Port Capacity Bar -->
                <div class="bg-slate-50 dark:bg-slate-800/80 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80 mb-2.5">
                    <div class="flex items-center justify-between text-[10px] font-bold mb-1">
                        <span>Okupansi Port: ${usedPorts}/${totalPorts}</span>
                        <span class="${occupancy >= 100 ? 'text-amber-500' : 'text-emerald-500'}">${occupancy}%</span>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden mb-1">
                        <div class="h-full rounded-full ${occupancy >= 100 ? 'bg-amber-500' : 'bg-emerald-500'}" style="width: ${occupancy}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[9px] text-slate-400">
                        <span>Tersedia: <strong class="text-emerald-600">${availablePorts} Port</strong></span>
                        <span>Pelanggan: <strong class="text-indigo-600">${custCount} User</strong></span>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-2 truncate" title="${safeAddress}">
                    <i class="fa-solid fa-location-dot text-slate-400 mr-1"></i>${safeAddress}
                </div>

                ${odp.notes ? `<div class="text-[10px] text-slate-400 italic mb-3"><i class="fa-solid fa-note-sticky mr-1"></i>${safeNotes}</div>` : ''}

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="openOdpPortsModal(${odp.id}, '${safeCode}')" class="col-span-2 px-2 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold flex items-center justify-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-plug text-xs"></i> Kelola Port & Pelanggan (${custCount})
                    </button>
                    <button type="button" onclick="openUploadPhotoModal(${odp.id}, '${safeCode}')" class="px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[11px] font-semibold flex items-center justify-center gap-1">
                        <i class="fa-solid fa-camera text-sky-500"></i> ${odp.photo_url ? 'Ganti Foto' : 'Upload Foto'}
                    </button>
                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" rel="noopener noreferrer" class="px-2 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold flex items-center justify-center gap-1">
                        <i class="fa-solid fa-diamond-turn-right"></i> Rute GPS
                    </a>
                </div>
            </div>
        `;

        marker.bindPopup(popupContent, { maxWidth: 320 });
        odpMarkerClusterGroup.addLayer(marker);
    });

    odpMap.addLayer(odpMarkerClusterGroup);

    if (bounds.length > 0) {
        odpMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
    }

    // Map Click Listener for Coordinate Picking Mode
    odpMap.on('click', function(e) {
        if (!isPickingMode) return;

        const pickedLat = e.latlng.lat.toFixed(6);
        const pickedLng = e.latlng.lng.toFixed(6);

        if (pickingTarget === 'add') {
            document.getElementById('addOdpLat').value = pickedLat;
            document.getElementById('addOdpLng').value = pickedLng;
            openAddOdpModal();
        } else if (pickingTarget === 'edit') {
            document.getElementById('editLatitude').value = pickedLat;
            document.getElementById('editLongitude').value = pickedLng;
            document.getElementById('editOdpModal').classList.remove('hidden');
        }

        cancelMapPicking();
    });
}

function centerOdpMapOnMarkers() {
    if (!odpMap || !odpMarkerClusterGroup) return;
    const bounds = odpMarkerClusterGroup.getBounds();
    if (bounds.isValid()) {
        odpMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
    }
}

function locateUserPositionOdp() {
    if (!navigator.geolocation || !odpMap) {
        alert('Geolocation tidak didukung pada browser Anda.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            odpMap.setView([lat, lng], 17);
            const userIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: #0284c7; width: 22px; height: 22px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 12px rgba(2,132,199,0.8); display: flex; align-items: center; justify-content: center; color: white;"><i class="fa-solid fa-person-walking text-[10px]"></i></div>`,
                iconSize: [22, 22],
                iconAnchor: [11, 11]
            });
            L.marker([lat, lng], { icon: userIcon }).addTo(odpMap).bindPopup('<b>Lokasi Anda Saat Ini</b>').openPopup();
        },
        err => alert('Gagal membaca lokasi GPS: ' + err.message),
        { enableHighAccuracy: true }
    );
}

function toggleOdpSatelliteLayer() {
    if (!odpMap) return;
    const btnText = document.getElementById('layerNameTextOdp');
    if (currentOdpLayerType === 'osm') {
        odpMap.removeLayer(odpOsmLayer);
        odpMap.addLayer(odpSatelliteLayer);
        currentOdpLayerType = 'satellite';
        if (btnText) btnText.textContent = 'Jalan (OSM)';
    } else {
        odpMap.removeLayer(odpSatelliteLayer);
        odpMap.addLayer(odpOsmLayer);
        currentOdpLayerType = 'osm';
        if (btnText) btnText.textContent = 'Satelit';
    }
}

function flyToOdpCoordinates(lat, lng, code) {
    if (!odpMap) return;
    window.scrollTo({ top: document.getElementById('odpMap').offsetTop - 80, behavior: 'smooth' });
    odpMap.flyTo([parseFloat(lat), parseFloat(lng)], 18, { duration: 1.2 });
}

// Coordinate picking mode
function startPickingCoordinatesOnMap(target) {
    isPickingMode = true;
    pickingTarget = target;

    // Temporarily hide open modals to allow picking
    if (target === 'add') {
        document.getElementById('addOdpModal').classList.add('hidden');
    } else if (target === 'edit') {
        document.getElementById('editOdpModal').classList.add('hidden');
    }

    document.getElementById('pickingNotification').classList.remove('hidden');
    document.getElementById('odpMap').classList.add('map-picking-mode');
    window.scrollTo({ top: document.getElementById('odpMap').offsetTop - 80, behavior: 'smooth' });
}

function cancelMapPicking() {
    isPickingMode = false;
    document.getElementById('pickingNotification').classList.add('hidden');
    document.getElementById('odpMap').classList.remove('map-picking-mode');
}

// Modal Handlers
function openAddOdpModal() {
    document.getElementById('addOdpModal').classList.remove('hidden');
}
function closeAddOdpModal() {
    document.getElementById('addOdpModal').classList.add('hidden');
}

function openEditOdpModal(target) {
    let odp = target;
    if (target instanceof HTMLElement || (target && target.dataset && target.dataset.odp)) {
        try {
            odp = JSON.parse(atob(target.dataset.odp));
        } catch (e) {
            console.error('Failed to parse ODP data:', e);
            return;
        }
    }
    document.getElementById('editOdpForm').action = `/maps/odp/${odp.id}`;
    document.getElementById('editModalCodeHeader').textContent = odp.code_odp;
    document.getElementById('editCodeOdp').value = odp.code_odp;
    document.getElementById('editName').value = odp.name || '';
    document.getElementById('editBillingNodeId').value = odp.billing_node_id || '';
    document.getElementById('editLatitude').value = odp.latitude || '';
    document.getElementById('editLongitude').value = odp.longitude || '';
    document.getElementById('editTotalPorts').value = odp.total_ports || 16;
    document.getElementById('editUsedPorts').value = odp.used_ports || 0;
    document.getElementById('editStatus').value = odp.status || 'active';
    document.getElementById('editAddress').value = odp.address || '';
    document.getElementById('editNotes').value = odp.notes || '';
    document.getElementById('editOdpModal').classList.remove('hidden');
}
function closeEditOdpModal() {
    document.getElementById('editOdpModal').classList.add('hidden');
}

function openUploadPhotoModal(id, code) {
    document.getElementById('uploadPhotoForm').action = `/maps/odp/${id}/photo`;
    document.getElementById('uploadPhotoCodeHeader').textContent = code;
    document.getElementById('uploadPhotoModal').classList.remove('hidden');
}
function closeUploadPhotoModal() {
    document.getElementById('uploadPhotoModal').classList.add('hidden');
    document.getElementById('quickPhotoPreview').classList.add('hidden');
}

function previewOdpImage(input, previewContainerId) {
    const container = document.getElementById(previewContainerId);
    if (!container) return;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = container.querySelector('img');
            if (img) img.src = e.target.result;
            container.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Lightbox Handlers
function openLightbox(url, caption) {
    document.getElementById('lightboxImage').src = url;
    document.getElementById('lightboxCaption').textContent = 'Foto Fisik: ' + caption;
    document.getElementById('lightboxDownloadBtn').href = url;
    document.getElementById('odpLightboxModal').classList.remove('hidden');
}
function closeLightbox() {
    document.getElementById('odpLightboxModal').classList.add('hidden');
    document.getElementById('lightboxImage').src = '';
}

// ==========================================
// PORT ALLOCATION & CUSTOMER MANAGEMENT MODAL
// ==========================================
let currentActiveOdpId = null;
let currentActiveOdpCode = '';
let currentTargetPortNumber = null;
let searchDebounceTimer = null;
const canEditPorts = document.getElementById('odpPortsModal')?.dataset.canEdit === 'true';

function openOdpPortsModal(odpId, codeOdp) {
    currentActiveOdpId = odpId;
    currentActiveOdpCode = codeOdp;

    document.getElementById('portsModalOdpCode').textContent = codeOdp;
    document.getElementById('portsModalBilling').textContent = 'Memuat server...';
    document.getElementById('portsModalCapacity').textContent = '';
    document.getElementById('portsTableBody').innerHTML = '';
    document.getElementById('portsModalLoading').classList.remove('hidden');
    document.getElementById('portsModalContent').classList.add('hidden');
    document.getElementById('unassignedCustomersSection').classList.add('hidden');
    document.getElementById('unassignedCustomersList').innerHTML = '';

    const roleNotice = document.getElementById('portsModalRoleNotice');
    if (roleNotice) {
        if (!canEditPorts) {
            roleNotice.classList.remove('hidden');
        } else {
            roleNotice.classList.add('hidden');
        }
    }

    document.getElementById('odpPortsModal').classList.remove('hidden');

    loadOdpPortsData(odpId);
}

function closeOdpPortsModal() {
    document.getElementById('odpPortsModal').classList.add('hidden');
    currentActiveOdpId = null;
    currentActiveOdpCode = '';
}

function loadOdpPortsData(odpId) {
    fetch(`/maps/odp/${odpId}/ports`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => {
        if (!res.ok) throw new Error('Gagal memuat data port');
        return res.json();
    })
    .then(data => {
        document.getElementById('portsModalLoading').classList.add('hidden');
        document.getElementById('portsModalContent').classList.remove('hidden');

        const odp = data.odp;
        document.getElementById('portsModalOdpCode').textContent = odp.code_odp;
        document.getElementById('portsModalBilling').textContent = odp.billing_node ? `[${odp.billing_node.tenant_code}] ${odp.billing_node.name}` : 'Semua Server';
        document.getElementById('portsModalCapacity').textContent = `Terpakai: ${odp.used_ports}/${odp.total_ports} Port`;

        renderPortsTable(data.ports, data.can_edit);
        renderUnassignedCustomers(data.unassigned, data.can_edit);
    })
    .catch(err => {
        document.getElementById('portsModalLoading').innerHTML = `
            <div class="text-rose-500 font-semibold p-4">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Terjadi kesalahan saat memuat port: ${err.message}
            </div>
        `;
    });
}

function renderPortsTable(ports, canEdit) {
    const tbody = document.getElementById('portsTableBody');
    tbody.innerHTML = '';

    if (!ports || ports.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="p-6 text-center text-slate-400">Tidak ada slot port tersedia.</td></tr>`;
        return;
    }

    ports.forEach(p => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors';

        // 1. Port Number
        const portCell = document.createElement('td');
        portCell.className = 'py-3 px-4 text-center font-bold text-slate-700 dark:text-slate-300 font-mono text-sm';
        portCell.textContent = p.port_number;

        // 2. Customer Name & Info (matching image 2: Fikrih - Standard)
        const nameCell = document.createElement('td');
        nameCell.className = 'py-3 px-4';
        if (p.customer) {
            const cust = p.customer;
            nameCell.innerHTML = `
                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                    <span>${escapeHtml(cust.name)}</span>
                    ${cust.billing_node ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">[${escapeHtml(cust.billing_node.tenant_code)}]</span>` : ''}
                    <span class="text-slate-400 font-normal text-[11px]">- ${escapeHtml(cust.package_name || 'Standard')}</span>
                </div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">${escapeHtml(cust.no_services)}</span>
                    ${cust.phone ? ` • <i class="fa-brands fa-whatsapp text-emerald-500 text-[9px]"></i> ${escapeHtml(cust.phone)}` : ''}
                    ${cust.address ? ` • <span title="${escapeHtml(cust.address)}">${escapeHtml(cust.address.substring(0, 35))}${cust.address.length > 35 ? '...' : ''}</span>` : ''}
                </div>
            `;
        } else {
            nameCell.innerHTML = `<span class="text-slate-400 dark:text-slate-500 italic text-[11px]">Port Kosong (Tersedia)</span>`;
        }

        // 3. Status Badge (matching image 2: Aktif)
        const statusCell = document.createElement('td');
        statusCell.className = 'py-3 px-4 text-center';
        if (p.customer) {
            const st = (p.customer.status || '').toLowerCase();
            if (st === 'active' || st === 'aktif') {
                statusCell.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40">Aktif</span>`;
            } else if (st === 'isolated' || st === 'isolir') {
                statusCell.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40">Isolir</span>`;
            } else {
                statusCell.innerHTML = `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300">Non-Aktif</span>`;
            }
        } else {
            statusCell.innerHTML = `<span class="text-slate-300 dark:text-slate-600">-</span>`;
        }

        // 4. Action Buttons (matching image 2: Edit pen icon)
        const actionCell = document.createElement('td');
        actionCell.className = 'py-3 px-4 text-center';

        if (canEdit) {
            if (p.customer) {
                actionCell.innerHTML = `
                    <div class="flex items-center justify-center gap-1">
                        <button type="button" onclick="openAssignPortModal(${p.port_number})" class="w-8 h-8 rounded-lg text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 flex items-center justify-center transition-colors" title="Ubah / Ganti Pelanggan Port ${p.port_number}">
                            <i class="fa-solid fa-pen-to-square text-sm"></i>
                        </button>
                        <button type="button" onclick="detachCustomerPort(${p.port_number}, '${escapeHtml(p.customer.name)}')" class="w-8 h-8 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/50 flex items-center justify-center transition-colors" title="Lepas Pelanggan dari Port ${p.port_number}">
                            <i class="fa-solid fa-link-slash text-xs"></i>
                        </button>
                    </div>
                `;
            } else {
                actionCell.innerHTML = `
                    <button type="button" onclick="openAssignPortModal(${p.port_number})" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 dark:text-emerald-300 text-[11px] font-bold border border-emerald-200 dark:border-emerald-800/40 transition-colors mx-auto">
                        <i class="fa-solid fa-plus text-[10px]"></i> Pasang
                    </button>
                `;
            }
        } else {
            actionCell.innerHTML = `<span class="text-[10px] text-slate-400 font-semibold italic"><i class="fa-solid fa-lock text-[9px] mr-0.5"></i>Read-Only</span>`;
        }

        tr.appendChild(portCell);
        tr.appendChild(nameCell);
        tr.appendChild(statusCell);
        tr.appendChild(actionCell);
        tbody.appendChild(tr);
    });
}

function renderUnassignedCustomers(unassigned, canEdit) {
    const sec = document.getElementById('unassignedCustomersSection');
    const list = document.getElementById('unassignedCustomersList');
    list.innerHTML = '';

    if (!unassigned || unassigned.length === 0) {
        sec.classList.add('hidden');
        return;
    }

    sec.classList.remove('hidden');
    unassigned.forEach(c => {
        const item = document.createElement('div');
        item.className = 'inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700/60 text-[11px] text-slate-800 dark:text-slate-200';
        item.innerHTML = `
            <span>${c.billing_node ? `<strong class="text-indigo-600 dark:text-indigo-400 font-mono">[${escapeHtml(c.billing_node.tenant_code)}]</strong> ` : ''}<strong>${escapeHtml(c.name)}</strong> (${escapeHtml(c.no_services)})</span>
            ${canEdit ? `<button type="button" onclick="quickAssignUnassigned(${c.id}, '${escapeHtml(c.name)}')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold">Pilih Port</button>` : ''}
        `;
        list.appendChild(item);
    });
}

function quickAssignUnassigned(customerId, customerName) {
    const port = prompt(`Masukkan nomor port (1-${document.getElementById('portsModalCapacity').textContent || '16'}) untuk [${customerName}]:`, '1');
    if (!port) return;
    executeAssignCustomerToPort(parseInt(port), customerId);
}

// ==========================================
// SUB-MODAL ASSIGN CUSTOMER
// ==========================================
function openAssignPortModal(portNumber) {
    if (!canEditPorts) return;
    currentTargetPortNumber = portNumber;
    document.getElementById('assignModalPortTitle').textContent = `Port #${portNumber}`;
    document.getElementById('assignSearchInput').value = '';
    document.getElementById('assignPortModal').classList.remove('hidden');

    searchCustomersForPort('');
}

function closeAssignPortModal() {
    document.getElementById('assignPortModal').classList.add('hidden');
    currentTargetPortNumber = null;
}

function debounceSearchCustomers() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        const q = document.getElementById('assignSearchInput').value;
        searchCustomersForPort(q);
    }, 300);
}

function searchCustomersForPort(query) {
    const container = document.getElementById('assignSearchResultsList');
    container.innerHTML = `<div class="p-4 text-center text-xs text-slate-400"><i class="fa-solid fa-circle-notch fa-spin text-emerald-500 mr-1"></i> Mencari...</div>`;

    fetch(`/maps/odp/${currentActiveOdpId}/search-customers?q=${encodeURIComponent(query)}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.customers || data.customers.length === 0) {
            container.innerHTML = `<div class="p-6 text-center text-xs text-slate-400">Tidak ada pelanggan yang cocok di server billing ini.</div>`;
            return;
        }

        container.innerHTML = '';
        data.customers.forEach(c => {
            const isCurrentlyOnOdp = (c.odp_name === currentActiveOdpCode);
            const div = document.createElement('div');
            div.className = 'p-3 hover:bg-emerald-50/60 dark:hover:bg-emerald-950/30 flex items-center justify-between gap-3 cursor-pointer transition-colors';
            div.onclick = () => {
                if (confirm(`Pasang pelanggan [${c.name}] (${c.no_services}) ke Port #${currentTargetPortNumber}?`)) {
                    executeAssignCustomerToPort(currentTargetPortNumber, c.id);
                }
            };

            div.innerHTML = `
                <div>
                    <div class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span>${escapeHtml(c.name)}</span>
                        ${isCurrentlyOnOdp ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-700">Port Saat Ini: ${c.port_number || 'Belum diatur'}</span>` : ''}
                    </div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                        <span class="text-emerald-600 font-semibold">${escapeHtml(c.no_services)}</span>
                        ${c.package_name ? ` • ${escapeHtml(c.package_name)}` : ''}
                        ${c.phone ? ` • ${escapeHtml(c.phone)}` : ''}
                    </div>
                    ${c.address ? `<div class="text-[10px] text-slate-400 truncate max-w-sm">${escapeHtml(c.address)}</div>` : ''}
                </div>
                <button type="button" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-sm flex items-center gap-1 flex-shrink-0">
                    <i class="fa-solid fa-check text-[10px]"></i> Pilih
                </button>
            `;
            container.appendChild(div);
        });
    })
    .catch(err => {
        container.innerHTML = `<div class="p-4 text-center text-xs text-rose-500">Gagal mencari data: ${err.message}</div>`;
    });
}

function executeAssignCustomerToPort(portNumber, customerId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || document.querySelector('input[name="_token"]')?.value;

    fetch(`/maps/odp/${currentActiveOdpId}/assign-port`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            port_number: portNumber,
            customer_id: customerId
        })
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Gagal memasang pelanggan ke port.');
            return;
        }

        closeAssignPortModal();
        loadOdpPortsData(currentActiveOdpId);
    })
    .catch(err => {
        alert('Terjadi kesalahan jaringan: ' + err.message);
    });
}

function detachCustomerPort(portNumber, customerName) {
    if (!canEditPorts) return;
    if (!confirm(`Apakah Anda yakin ingin melepas pelanggan [${customerName}] dari Port #${portNumber}?`)) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || document.querySelector('input[name="_token"]')?.value;

    fetch(`/maps/odp/${currentActiveOdpId}/detach-port`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            port_number: portNumber
        })
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Gagal melepas pelanggan dari port.');
            return;
        }
        loadOdpPortsData(currentActiveOdpId);
    })
    .catch(err => {
        alert('Terjadi kesalahan jaringan: ' + err.message);
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endpush
