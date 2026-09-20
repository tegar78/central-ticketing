@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-800 to-slate-800/80 p-6 rounded-2xl border border-slate-700/60 shadow-xl">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <span>Dashboard Management Tiket</span>
                @if($user->role === 'technician')
                    <span class="text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-3 py-1 rounded-full font-medium">Mode Teknisi (Assigned Only)</span>
                @else
                    <span class="text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-1 rounded-full font-medium">Mode Admin/Operator (All 60 Billing)</span>
                @endif
            </h1>
            <p class="text-slate-400 text-sm mt-1">
                @if($user->role === 'technician')
                    Menampilkan tiket yang **ditugaskan khusus** untuk akun Anda ({{ $user->name }}).
                @else
                    Mengelola seluruh tiket gangguan dari 60 aplikasi billing mitra terhubung.
                @endif
            </p>
        </div>
        @if(in_array($user->role, ['admin', 'operator']))
        <div>
            <button onclick="document.getElementById('createTicketModal').classList.remove('hidden')" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-blue-600/30 transition-all hover:shadow-blue-500/40 hover:scale-105">
                <i class="fa-solid fa-plus"></i> Tambah Tiket
            </button>
        </div>
        @endif
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card Total -->
        <div class="bg-slate-800/70 border border-slate-700/60 p-5 rounded-2xl shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-slate-700/30 text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-ticket"></i></div>
            <div class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Total Tiket</div>
            <div class="text-3xl font-bold text-white mt-2">{{ number_format($totalCount) }}</div>
            <div class="text-xs text-slate-500 mt-1">Tiket terdaftar di sistem</div>
        </div>

        <!-- Card Pending -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'pending'])) }}" 
           class="bg-slate-800/70 border {{ request('status') === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-amber-500/30' }} hover:border-amber-500/60 p-5 rounded-2xl shadow-lg relative overflow-hidden group transition-all">
            <div class="absolute -right-4 -bottom-4 text-amber-500/10 text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="text-xs font-semibold uppercase text-amber-400 tracking-wider">Tiket Pending</div>
            <div class="text-3xl font-bold text-amber-400 mt-2">{{ number_format($pendingCount) }}</div>
            <div class="text-xs text-amber-500/80 mt-1">Menunggu penanganan / penugasan</div>
        </a>

        <!-- Card Process -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'process'])) }}" 
           class="bg-slate-800/70 border {{ request('status') === 'process' ? 'border-blue-500 ring-2 ring-blue-500/30' : 'border-blue-500/30' }} hover:border-blue-500/60 p-5 rounded-2xl shadow-lg relative overflow-hidden group transition-all">
            <div class="absolute -right-4 -bottom-4 text-blue-500/10 text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-spinner"></i></div>
            <div class="text-xs font-semibold uppercase text-blue-400 tracking-wider">Tiket Proces</div>
            <div class="text-3xl font-bold text-blue-400 mt-2">{{ number_format($processCount) }}</div>
            <div class="text-xs text-blue-500/80 mt-1">Sedang dikerjakan teknisi</div>
        </a>

        <!-- Card Close -->
        <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => 'close'])) }}" 
           class="bg-slate-800/70 border {{ request('status') === 'close' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-emerald-500/30' }} hover:border-emerald-500/60 p-5 rounded-2xl shadow-lg relative overflow-hidden group transition-all">
            <div class="absolute -right-4 -bottom-4 text-emerald-500/10 text-6xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-circle-check"></i></div>
            <div class="text-xs font-semibold uppercase text-emerald-400 tracking-wider">Tiket Done / Close</div>
            <div class="text-3xl font-bold text-emerald-400 mt-2">{{ number_format($closeCount) }}</div>
            <div class="text-xs text-emerald-500/80 mt-1">Selesai diperbaiki</div>
        </a>
    </div>

    <!-- Filters Section -->
    <div class="bg-slate-800/50 border border-slate-700/60 p-5 rounded-2xl shadow-lg">
        <form action="{{ route('dashboard') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Pencarian</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="No tiket, nama, no layanan..." 
                           class="w-full pl-9 pr-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Status Tiket</label>
                <select name="status" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="process" {{ request('status') === 'process' ? 'selected' : '' }}>Proces</option>
                    <option value="close" {{ request('status') === 'close' ? 'selected' : '' }}>Close</option>
                </select>
            </div>

            @if($user->role !== 'technician')
            <!-- Billing Instance Filter (Admin/Operator) -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Aplikasi Billing Mitra</label>
                <select name="billing_instance_id" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500">
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
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Teknisi Penanggung Jawab</label>
                <select name="technician_id" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500">
                    <option value="">Semua Teknisi</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                            {{ $tech->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-span-full flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl border border-slate-700 transition-all">Reset Filter</a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow-lg shadow-blue-600/30 transition-all">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- Tickets Table Card -->
    <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-5 border-b border-slate-700/60 flex items-center justify-between">
            <h3 class="font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-blue-400"></i>
                <span>Daftar Tiket Gangguan</span>
            </h3>
            <span class="text-xs text-slate-400">Menampilkan {{ $tickets->count() }} dari {{ $tickets->total() }} tiket</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-700/60">
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
                <tbody class="divide-y divide-slate-800">
                    @forelse($tickets as $index => $ticket)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-4 text-center font-medium text-slate-400">
                                {{ $tickets->firstItem() + $index }}.
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-200">{{ $ticket->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-500">{{ $ticket->created_at->format('H:i:s') }} WIB</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-bold text-blue-400">{{ $ticket->ticket_number }}</div>
                                <div class="inline-flex items-center gap-1 mt-1 text-[10px] bg-slate-900 border border-slate-700/80 px-2 py-0.5 rounded-full text-slate-400">
                                    <i class="fa-solid fa-building text-[9px] text-indigo-400"></i>
                                    <span>{{ $ticket->billingInstance->name ?? 'Unknown Billing' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-200">{{ $ticket->customer_name }}</div>
                                <div class="text-[10px] text-slate-500">{{ $ticket->customer_phone ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-mono text-slate-300">
                                {{ $ticket->no_services }}
                            </td>
                            <td class="px-4 py-4 max-w-xs">
                                @if($ticket->category_name)
                                    <span class="text-[10px] font-semibold text-indigo-300 bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-500/30 block w-fit mb-1">{{ $ticket->category_name }}</span>
                                @endif
                                <p class="line-clamp-2 text-slate-400 text-[11px]">{{ $ticket->problem_description }}</p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($ticket->assignedTechnician)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-[10px] font-bold">
                                            <i class="fa-solid fa-user-gear"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-emerald-300">{{ $ticket->assignedTechnician->name }}</div>
                                            <div class="text-[10px] text-slate-500">{{ $ticket->assignedTechnician->phone }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] text-rose-400 bg-rose-500/10 border border-rose-500/20 px-2.5 py-1 rounded-full">
                                        <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Belum ditugaskan
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                @if($ticket->status === 'pending')
                                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 uppercase tracking-wider">Pending</span>
                                @elseif($ticket->status === 'process')
                                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 uppercase tracking-wider">Proces</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase tracking-wider">Close</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                <a href="{{ route('tickets.show', $ticket->id) }}" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white font-medium rounded-lg border border-blue-500/30 hover:border-blue-600 transition-all text-xs">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                <i class="fa-solid fa-inbox text-4xl mb-3 block text-slate-600"></i>
                                Tidak ada data tiket yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-slate-700/60 bg-slate-900/60">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal Tambah Tiket --}}
@if(in_array($user->role, ['admin', 'operator']))
<div id="createTicketModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('createTicketModal').classList.add('hidden')"></div>
    
    {{-- Modal Content --}}
    <div class="relative bg-slate-800 border border-slate-700/60 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-modal">
        {{-- Header --}}
        <div class="sticky top-0 bg-slate-800 border-b border-slate-700/60 p-5 rounded-t-2xl flex items-center justify-between z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-ticket text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Tambah Tiket Gangguan</h2>
                    <p class="text-xs text-slate-400">Pilih pelanggan billing & buat tiket gangguan</p>
                </div>
            </div>
            <button onclick="document.getElementById('createTicketModal').classList.add('hidden')" 
                    class="w-8 h-8 rounded-lg bg-slate-700/50 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Form --}}
        <form action="{{ route('tickets.store') }}" method="POST" class="p-5 space-y-4">
            @csrf

            {{-- Select & Cari Pelanggan dari Aplikasi Billing --}}
            <div class="bg-slate-900/90 p-4 rounded-xl border border-blue-500/30 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold uppercase text-blue-400">
                        <i class="fa-solid fa-address-book text-blue-400 mr-1"></i> No Layanan - Nama Pelanggan <span class="text-[11px] font-normal text-slate-400 lowercase">(select & cari data billing)</span>
                    </label>
                    <span id="customerCountBadge" class="text-[10px] bg-blue-500/20 text-blue-300 border border-blue-500/30 px-2 py-0.5 rounded-full font-medium">
                        {{ count($customersList) }} Pelanggan
                    </span>
                </div>

                {{-- Quick Search Input --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" id="customerSearchBox" oninput="filterCustomerSelectOptions(this.value)" 
                           placeholder="Cari berdasarkan Nomor Layanan (contoh: 220509...) atau Nama (contoh: Soleha)..." 
                           class="w-full pl-9 pr-8 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                    <button type="button" onclick="clearCustomerSearchBox()" id="clearCustomerSearchBtn" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Select Dropdown --}}
                <select id="selectCustomerBilling" onchange="autoFillCustomerData(this)" 
                        class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
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
                <p class="text-[10px] text-slate-400">Ketik nomor layanan atau nama pelanggan pada kolom cari di atas untuk memfilter pilihan secara instan.</p>
            </div>

            {{-- Billing Instance --}}
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5 flex items-center justify-between">
                    <span><i class="fa-solid fa-building text-indigo-400 mr-1"></i> Aplikasi Billing Mitra <span class="text-rose-400">*</span></span>
                    <span id="customerLoadingState" class="hidden text-[10px] text-blue-400 font-normal lowercase flex items-center gap-1">
                        <i class="fa-solid fa-circle-notch fa-spin"></i> mengambil data pelanggan billing...
                    </span>
                </label>
                <select name="billing_instance_id" required onchange="fetchCustomersFromSelectedBilling(this.value)"
                        class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                    <option value="">-- Pilih Billing Instance --</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ old('billing_instance_id') == $t->id ? 'selected' : '' }}>{{ $t->tenant_code }} - {{ $t->name }}</option>
                    @endforeach
                </select>
                @error('billing_instance_id') <span class="text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Nama Pelanggan --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                        <i class="fa-solid fa-user text-blue-400 mr-1"></i> Nama Pelanggan <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="customer_name" required value="{{ old('customer_name') }}" placeholder="Nama lengkap pelanggan"
                           class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                    @error('customer_name') <span class="text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- No Layanan --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                        <i class="fa-solid fa-hashtag text-blue-400 mr-1"></i> No Layanan <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="no_services" required value="{{ old('no_services') }}" placeholder="Nomor layanan pelanggan"
                           class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                    @error('no_services') <span class="text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- No Telp --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                        <i class="fa-solid fa-phone text-emerald-400 mr-1"></i> No Telp / WhatsApp
                    </label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="08xxxxxxxxxx"
                           class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                        <i class="fa-solid fa-tag text-indigo-400 mr-1"></i> Kategori Gangguan
                    </label>
                    <input type="text" name="category_name" value="{{ old('category_name') }}" placeholder="Contoh: FO Putus, No Signal, dll"
                           class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                </div>
            </div>

            {{-- Alamat --}}
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                    <i class="fa-solid fa-location-dot text-rose-400 mr-1"></i> Alamat Pelanggan
                </label>
                <textarea name="customer_address" rows="2" placeholder="Alamat lengkap pelanggan"
                          class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">{{ old('customer_address') }}</textarea>
            </div>

            {{-- Deskripsi Masalah --}}
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                    <i class="fa-solid fa-file-lines text-amber-400 mr-1"></i> Deskripsi Masalah <span class="text-rose-400">*</span>
                </label>
                <textarea name="problem_description" rows="3" required placeholder="Jelaskan detail gangguan yang dialami pelanggan..."
                          class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">{{ old('problem_description') }}</textarea>
                @error('problem_description') <span class="text-rose-400 text-[10px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Koordinat (Optional) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                        <i class="fa-solid fa-map-pin text-blue-400 mr-1"></i> Latitude <span class="text-slate-600">(opsional)</span>
                    </label>
                    <input type="text" name="latitude" value="{{ old('latitude') }}" placeholder="-6.xxxxxx"
                           class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                        <i class="fa-solid fa-map-pin text-blue-400 mr-1"></i> Longitude <span class="text-slate-600">(opsional)</span>
                    </label>
                    <input type="text" name="longitude" value="{{ old('longitude') }}" placeholder="106.xxxxxx"
                           class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-700/60">
                <button type="button" onclick="document.getElementById('createTicketModal').classList.add('hidden')" 
                        class="px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-medium rounded-xl border border-slate-600 transition-all">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Simpan
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

<script>
let allRawCustomers = @json($customersList);

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
