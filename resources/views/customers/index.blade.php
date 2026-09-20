@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-800 via-slate-800 to-indigo-950/80 p-6 rounded-2xl border border-slate-700/60 shadow-xl">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-users-gear text-lg"></i>
                </div>
                <span>Direktori Terpusat Pelanggan (60 Billing Nodes)</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">
                Pusat data pelanggan tersinkronisasi dari 60 server billing CodeIgniter 3 mitra.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('customers.syncBilling') }}" method="POST">
                @csrf
                <input type="hidden" name="tenant_code" value="BILL-001">
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all">
                    <i class="fa-solid fa-rotate"></i> Sync Billing Bill-GYH
                </button>
            </form>
            <a href="{{ route('dashboard') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-medium rounded-xl border border-slate-600 transition-all">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Total Pelanggan -->
        <div class="bg-slate-800/70 border border-slate-700/60 p-4 rounded-2xl shadow-lg relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 text-slate-700/30 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-users"></i></div>
            <div class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Total Pelanggan</div>
            <div class="text-2xl font-bold text-white mt-1">{{ number_format($totalCustomers) }}</div>
            <div class="text-xs text-slate-500 mt-1">Tersinkron dari bill-gyh</div>
        </div>

        <!-- Pelanggan Active -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'active'])) }}"
           class="bg-slate-800/70 border {{ request('status') === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-emerald-500/30' }} hover:border-emerald-500/60 p-4 rounded-2xl shadow-lg relative overflow-hidden group transition-all">
            <div class="absolute -right-4 -bottom-4 text-emerald-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-circle-check"></i></div>
            <div class="text-xs font-semibold uppercase text-emerald-400 tracking-wider">Aktif</div>
            <div class="text-2xl font-bold text-emerald-400 mt-1">{{ number_format($activeCustomers) }}</div>
            <div class="text-xs text-emerald-500/80 mt-1">Layanan aktif normal</div>
        </a>

        <!-- Pelanggan Isolated -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'isolated'])) }}"
           class="bg-slate-800/70 border {{ request('status') === 'isolated' ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-amber-500/30' }} hover:border-amber-500/60 p-4 rounded-2xl shadow-lg relative overflow-hidden group transition-all">
            <div class="absolute -right-4 -bottom-4 text-amber-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="text-xs font-semibold uppercase text-amber-400 tracking-wider">Isolir</div>
            <div class="text-2xl font-bold text-amber-400 mt-1">{{ number_format($isolatedCustomers) }}</div>
            <div class="text-xs text-amber-500/80 mt-1">Terisolir / Menunggak</div>
        </a>

        <!-- Pelanggan Inactive -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'inactive'])) }}"
           class="bg-slate-800/70 border {{ request('status') === 'inactive' ? 'border-rose-500 ring-2 ring-rose-500/30' : 'border-rose-500/30' }} hover:border-rose-500/60 p-4 rounded-2xl shadow-lg relative overflow-hidden group transition-all">
            <div class="absolute -right-4 -bottom-4 text-rose-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-user-xmark"></i></div>
            <div class="text-xs font-semibold uppercase text-rose-400 tracking-wider">Non-Aktif</div>
            <div class="text-2xl font-bold text-rose-400 mt-1">{{ number_format($inactiveCustomers) }}</div>
            <div class="text-xs text-rose-500/80 mt-1">Berhenti berlangganan</div>
        </a>

        <!-- Pelanggan Free -->
        <a href="{{ route('customers.index', array_merge(request()->query(), ['status' => 'free'])) }}"
           class="bg-slate-800/70 border {{ request('status') === 'free' ? 'border-sky-500 ring-2 ring-sky-500/30' : 'border-sky-500/30' }} hover:border-sky-500/60 p-4 rounded-2xl shadow-lg relative overflow-hidden group transition-all">
            <div class="absolute -right-4 -bottom-4 text-sky-500/10 text-5xl group-hover:scale-110 transition-transform"><i class="fa-solid fa-gift"></i></div>
            <div class="text-xs font-semibold uppercase text-sky-400 tracking-wider">Free</div>
            <div class="text-2xl font-bold text-sky-400 mt-1">{{ number_format($freeCustomers) }}</div>
            <div class="text-xs text-sky-500/80 mt-1">Gratis / Promo</div>
        </a>
    </div>

    <!-- Global Search & Filters Section -->
    <div class="bg-slate-800/50 border border-slate-700/60 p-5 rounded-2xl shadow-lg space-y-4">
        <form action="{{ route('customers.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Global Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase text-blue-400 mb-1.5">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Global Search (Instan 60 Billing)
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari no layanan, nama, phone, alamat, atau nama ODP..." 
                           class="w-full pl-9 pr-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                </div>
            </div>

            <!-- Billing Node Origin Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                    <i class="fa-solid fa-server mr-1"></i> Asal Server Billing
                </label>
                <select name="billing_node_id" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                    <option value="">Semua Server Billing (60 Nodes)</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('billing_node_id') == $t->id ? 'selected' : '' }}>
                            [{{ $t->tenant_code }}] {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Subscription Status Filter -->
            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                    <i class="fa-solid fa-signal mr-1"></i> Status Langganan
                </label>
                <select name="status" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
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
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">
                    <i class="fa-solid fa-sitemap mr-1"></i> Filter ODP
                </label>
                <select name="odp_name" class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/30">
                    <option value="">Semua Titik ODP</option>
                    @foreach($odpList as $odp)
                        <option value="{{ $odp }}" {{ request('odp_name') === $odp ? 'selected' : '' }}>
                            {{ $odp }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-span-full flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
                <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl border border-slate-700 transition-all">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filter
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl shadow-lg shadow-blue-600/30 transition-all">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari Data Pelanggan
                </button>
            </div>
        </form>
    </div>

    <!-- Customer Directory Table Card -->
    <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-5 border-b border-slate-700/60 flex items-center justify-between">
            <h3 class="font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-table-list text-blue-400"></i>
                <span>Direktori Pelanggan Terpusat</span>
            </h3>
            <span class="text-xs text-slate-400">Menampilkan {{ $customers->count() }} dari {{ $customers->total() }} pelanggan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-700/60">
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
                <tbody class="divide-y divide-slate-800">
                    @forelse($customers as $index => $customer)
                        @php
                            $billing = $customer->billingNode ?? ($billingMap[$customer->billing_instance_id] ?? null);
                            $phoneClean = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : null;
                            if ($phoneClean && str_starts_with($phoneClean, '0')) {
                                $phoneClean = '62' . substr($phoneClean, 1);
                            }
                        @endphp
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-4 py-4 text-center font-medium text-slate-400">
                                {{ $customers->firstItem() + $index }}.
                            </td>
                            <!-- Billing Node Identity Badge -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($billing)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/30">
                                        <i class="fa-solid fa-server text-[10px]"></i>
                                        <span>[{{ $billing->tenant_code }}] {{ $billing->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-600">-</span>
                                @endif
                            </td>
                            <!-- No Layanan -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="font-mono font-bold text-blue-400 text-xs">{{ $customer->no_services }}</span>
                            </td>
                            <!-- Nama Pelanggan -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-blue-600/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($customer->name ?? $customer->customer_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-200">{{ $customer->name ?? $customer->customer_name }}</div>
                                        <div class="text-[10px] text-slate-500">ID Remote: #{{ $customer->remote_customer_id ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <!-- Kontak & Alamat -->
                            <td class="px-4 py-4 max-w-xs">
                                <div class="text-slate-300 font-medium">{{ $customer->phone ?? $customer->customer_phone ?? '-' }}</div>
                                <p class="line-clamp-1 text-slate-400 text-[11px] mt-0.5">{{ $customer->address ?? $customer->customer_address ?? '-' }}</p>
                            </td>
                            <!-- Paket & Tagihan -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-semibold text-indigo-300">{{ $customer->package_name ?? 'Regular' }}</div>
                                @if($customer->monthly_fee)
                                    <div class="text-[11px] text-slate-400">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}/bln</div>
                                @endif
                            </td>
                            <!-- Titik ODP -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($customer->odp_name)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-mono text-cyan-300 bg-cyan-500/10 border border-cyan-500/30 px-2 py-0.5 rounded">
                                        <i class="fa-solid fa-sitemap text-[9px]"></i> {{ $customer->odp_name }}
                                    </span>
                                @else
                                    <span class="text-slate-600">-</span>
                                @endif
                            </td>
                            <!-- Status Badge -->
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                @php $custStatus = strtolower($customer->status ?? 'active'); @endphp
                                @if($custStatus === 'active')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase tracking-wider">Active</span>
                                @elseif($custStatus === 'isolated')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 uppercase tracking-wider">Isolated</span>
                                @elseif($custStatus === 'free')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-500/20 text-sky-400 border border-sky-500/30 uppercase tracking-wider">Free</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 uppercase tracking-wider">Inactive</span>
                                @endif
                            </td>
                            <!-- Quick Actions -->
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- 1-Click WhatsApp -->
                                    @if($phoneClean)
                                        <a href="https://wa.me/{{ $phoneClean }}" target="_blank"
                                           class="px-2.5 py-1.5 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white rounded-lg border border-emerald-500/30 transition-all text-[11px] font-medium flex items-center gap-1"
                                           title="1-Click WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i> WA
                                        </a>
                                    @endif

                                    <!-- 1-Click Maps -->
                                    @if($customer->latitude && $customer->longitude)
                                        <a href="https://www.google.com/maps/place/{{ $customer->latitude }},{{ $customer->longitude }}" target="_blank"
                                           class="px-2.5 py-1.5 bg-cyan-600/20 hover:bg-cyan-600 text-cyan-400 hover:text-white rounded-lg border border-cyan-500/30 transition-all text-[11px] font-medium flex items-center gap-1"
                                           title="1-Click Google Maps">
                                            <i class="fa-solid fa-map-location-dot"></i> Maps
                                        </a>
                                    @endif

                                    <!-- Buat Tiket Gangguan -->
                                    <button type="button" 
                                            onclick="openCreateTicketForCustomer('{{ $customer->no_services }}', '{{ addslashes($customer->name ?? $customer->customer_name) }}', '{{ $customer->phone ?? $customer->customer_phone }}', '{{ addslashes($customer->address ?? $customer->customer_address) }}', '{{ $customer->billing_node_id ?? $customer->billing_instance_id }}')"
                                            class="px-2.5 py-1.5 bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white rounded-lg border border-blue-500/30 transition-all text-[11px] font-medium flex items-center gap-1"
                                            title="Buat Tiket Gangguan">
                                        <i class="fa-solid fa-ticket"></i> Tiket
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500">
                                <i class="fa-solid fa-user-slash text-4xl mb-3 block text-slate-600"></i>
                                @if(request('search'))
                                    Tidak ditemukan pelanggan dengan kata kunci "<strong class="text-slate-400">{{ request('search') }}</strong>".
                                @else
                                    Belum ada data pelanggan yang tersinkronisasi dari server billing.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-700/60 bg-slate-900/60">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Quick Ticket Creation from Customer List -->
<div id="quickTicketModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('quickTicketModal').classList.add('hidden')"></div>
    <div class="relative bg-slate-800 border border-slate-700/60 rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="bg-slate-800 border-b border-slate-700/60 p-5 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white">
                    <i class="fa-solid fa-ticket text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Buat Tiket Gangguan Instan</h2>
                    <p class="text-xs text-slate-400">Otomatis terisi data pelanggan yang dipilih</p>
                </div>
            </div>
            <button onclick="document.getElementById('quickTicketModal').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="billing_instance_id" id="qt_billing_id">

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Nama Pelanggan</label>
                <input type="text" name="customer_name" id="qt_customer_name" required readonly class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">No Layanan</label>
                    <input type="text" name="no_services" id="qt_no_services" required readonly class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-blue-400 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">No Telp</label>
                    <input type="text" name="customer_phone" id="qt_customer_phone" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Alamat</label>
                <textarea name="customer_address" id="qt_customer_address" rows="2" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Kategori Gangguan</label>
                <input type="text" name="category_name" placeholder="Contoh: FO Putus, Modem Red, Internet Lelet" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Deskripsi Masalah *</label>
                <textarea name="problem_description" rows="3" required placeholder="Detail laporan masalah pelanggan..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-700">
                <button type="button" onclick="document.getElementById('quickTicketModal').classList.add('hidden')" class="px-4 py-2 bg-slate-700 text-slate-300 rounded-xl text-xs">Batal</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-blue-600/30 flex items-center gap-1.5"><i class="fa-solid fa-paper-plane"></i> Buat Tiket</button>
            </div>
        </form>
    </div>
</div>

<script>
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
