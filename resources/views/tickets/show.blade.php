@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Back Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-white text-sm font-medium transition-colors">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <div class="flex items-center gap-2">
            @if($ticket->status === 'pending')
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 uppercase">Status: Pending</span>
            @elseif($ticket->status === 'process')
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30 uppercase">Status: Proces</span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase">Status: Close</span>
            @endif
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Ticket Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Info Card -->
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-6">
                <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <span>Detail Tiket #{{ $ticket->ticket_number }}</span>
                        </h2>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                            <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded font-semibold">{{ $ticket->billingInstance->name ?? 'Billing' }}</span>
                            <span>• Dilaporkan {{ $ticket->created_at->format('d M Y, H:i') }} WIB</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                        <span class="block font-semibold uppercase text-slate-400 text-[10px] mb-1">Nama Pelanggan</span>
                        <span class="text-sm font-bold text-white block">{{ $ticket->customer_name }}</span>
                    </div>

                    <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                        <span class="block font-semibold uppercase text-slate-400 text-[10px] mb-1">No Layanan / ID Sub</span>
                        <span class="text-sm font-mono font-bold text-blue-400 block">{{ $ticket->no_services }}</span>
                    </div>

                    <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                        <span class="block font-semibold uppercase text-slate-400 text-[10px] mb-1">No Telp / WhatsApp</span>
                        <span class="text-sm font-bold text-slate-200 block">{{ $ticket->customer_phone ?? '-' }}</span>
                        @if($ticket->customer_phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ticket->customer_phone) }}" target="_blank" 
                               class="inline-flex items-center gap-1.5 mt-2 px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-semibold transition-all">
                                <i class="fa-brands fa-whatsapp"></i> Hubungi WhatsApp
                            </a>
                        @endif
                    </div>

                    <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800">
                        <span class="block font-semibold uppercase text-slate-400 text-[10px] mb-1">Kategori Gangguan</span>
                        <span class="text-sm font-bold text-indigo-300 block">{{ $ticket->category_name ?? 'Umum' }}</span>
                    </div>
                </div>

                <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 text-xs">
                    <span class="block font-semibold uppercase text-slate-400 text-[10px] mb-1">Alamat Pelanggan</span>
                    <p class="text-slate-300 leading-relaxed">{{ $ticket->customer_address ?? 'Tidak ada catatan alamat.' }}</p>
                </div>

                <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 text-xs">
                    <span class="block font-semibold uppercase text-slate-400 text-[10px] mb-1">Keterangan / Laporan Masalah</span>
                    <p class="text-slate-200 leading-relaxed font-medium bg-slate-950/80 p-3 rounded-lg border border-slate-800/80">{{ $ticket->problem_description }}</p>
                </div>

                @if($ticket->latitude && $ticket->longitude)
                    <div class="flex items-center justify-between bg-blue-950/30 p-4 rounded-xl border border-blue-800/40">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-2xl text-blue-400"></i>
                            <div>
                                <div class="font-bold text-white text-xs">Koordinat Lokasi Pelanggan</div>
                                <div class="text-[11px] font-mono text-blue-300">{{ $ticket->latitude }}, {{ $ticket->longitude }}</div>
                            </div>
                        </div>
                        <a href="https://www.google.com/maps/place/{{ $ticket->latitude }},{{ $ticket->longitude }}" target="_blank"
                           class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-blue-600/30 transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-map"></i> Buka Google Maps
                        </a>
                    </div>
                @endif
            </div>

            <!-- Timeline History Card -->
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-6">
                <h3 class="font-bold text-white text-base flex items-center gap-2 border-b border-slate-700/60 pb-3">
                    <i class="fa-solid fa-timeline text-indigo-400"></i>
                    <span>Timeline Pengerjaan Tiket</span>
                </h3>

                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-700">
                    @foreach($ticket->timelines as $timeline)
                        <div class="relative group">
                            <!-- Icon Marker -->
                            <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-blue-500 ring-4 ring-slate-800 group-hover:scale-125 transition-transform"></div>
                            
                            <div class="bg-slate-900/80 p-4 rounded-xl border border-slate-800">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-bold text-slate-200">
                                        {{ $timeline->user->name ?? 'System' }} 
                                        <span class="text-[10px] text-blue-400 capitalize font-normal">({{ $timeline->user->role ?? 'Billing Client' }})</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500">{{ $timeline->created_at->format('d M Y, H:i') }} WIB</span>
                                </div>
                                <div class="text-xs text-slate-300 mt-1.5 font-medium">{{ $timeline->remark }}</div>
                                <div class="mt-2">
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-400">
                                        Status: {{ $timeline->status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Actions Panel -->
        <div class="space-y-6">
            <!-- Assigned Technician Status Box -->
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
                <h3 class="font-bold text-white text-sm uppercase tracking-wider text-slate-400 flex items-center gap-2 border-b border-slate-700/60 pb-3">
                    <i class="fa-solid fa-user-gear text-emerald-400"></i>
                    <span>Teknisi Penanggung Jawab</span>
                </h3>

                @if($ticket->assignedTechnician)
                    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 space-y-2">
                        <div class="text-base font-bold">{{ $ticket->assignedTechnician->name }}</div>
                        <div class="text-xs text-slate-400 flex items-center gap-2">
                            <i class="fa-solid fa-phone"></i> {{ $ticket->assignedTechnician->phone ?? '-' }}
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                        <span>Belum ditugaskan ke teknisi manapun.</span>
                    </div>
                @endif

                @if($user->role !== 'technician')
                    <!-- Form Assign Technician (Admin / Operator Only) -->
                    <form action="{{ route('tickets.assign', $ticket->id) }}" method="POST" class="pt-4 border-t border-slate-700/60 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold uppercase text-slate-400">Tugaskan / Ganti Teknisi</label>
                        <select name="technician_id" required class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500">
                            <option value="">-- Pilih Teknisi --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ $ticket->assigned_technician_id == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->name }} ({{ $tech->phone }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-user-check"></i> Simpan Penugasan Teknisi
                        </button>
                    </form>
                @endif
            </div>

            <!-- Form Update Status Tiket -->
            <div class="bg-slate-800/70 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
                <h3 class="font-bold text-white text-sm uppercase tracking-wider text-slate-400 flex items-center gap-2 border-b border-slate-700/60 pb-3">
                    <i class="fa-solid fa-pen-to-square text-blue-400"></i>
                    <span>Update Status & Catatan</span>
                </h3>

                <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Pilih Status Baru</label>
                        <select name="status" required class="w-full px-3 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-blue-500">
                            <option value="pending" {{ $ticket->status === 'pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                            <option value="process" {{ $ticket->status === 'process' ? 'selected' : '' }}>Proces (Sedang Dikerjakan)</option>
                            <option value="close" {{ $ticket->status === 'close' ? 'selected' : '' }}>Close (Selesai)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Catatan / Keterangan Penanganan</label>
                        <textarea name="remark" rows="4" required placeholder="Contoh: Kabel FO telah disambung kembali, koneksi sudah aktif normal..."
                                  class="w-full p-3 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
