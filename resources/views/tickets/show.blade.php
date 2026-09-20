@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Back Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white text-sm font-medium transition-colors px-3.5 py-2.5 rounded-xl hover:bg-slate-200/60 dark:hover:bg-slate-800/50 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px] w-fit">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <div class="flex items-center gap-2">
            @if($ticket->status === 'pending')
                <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30 uppercase tracking-wider">Status: Pending</span>
            @elseif($ticket->status === 'process')
                <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/20 dark:text-blue-300 dark:border-blue-500/30 uppercase tracking-wider">Status: Proces</span>
            @else
                <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30 uppercase tracking-wider">Status: Close</span>
            @endif
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
        <!-- Left 2 Cols: Ticket Information -->
        <div class="lg:col-span-2 space-y-5 sm:space-y-6">
            <!-- Ticket Info Card -->
            <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm dark:shadow-xl space-y-5 sm:space-y-6 relative overflow-hidden backdrop-blur-sm transition-colors">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/20 dark:via-white/20 to-transparent"></div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200/80 dark:border-white/10 pb-4 gap-2">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Detail Tiket #{{ $ticket->ticket_number }}</span>
                        </h2>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                            <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-500/30 px-2.5 py-0.5 rounded-lg font-semibold">{{ $ticket->billingInstance->name ?? 'Billing' }}</span>
                            <span>• Dilaporkan {{ $ticket->created_at->format('d M Y, H:i') }} WIB</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-xs">
                    <div class="bg-slate-50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200/60 dark:border-white/5">
                        <span class="block font-semibold uppercase text-slate-500 dark:text-slate-400 text-[10px] mb-1">Nama Pelanggan</span>
                        <span class="text-sm font-bold text-slate-900 dark:text-white block">{{ $ticket->customer_name }}</span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200/60 dark:border-white/5">
                        <span class="block font-semibold uppercase text-slate-500 dark:text-slate-400 text-[10px] mb-1">No Layanan / ID Sub</span>
                        <span class="text-sm font-mono font-bold text-blue-600 dark:text-blue-400 block">{{ $ticket->no_services }}</span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200/60 dark:border-white/5">
                        <span class="block font-semibold uppercase text-slate-500 dark:text-slate-400 text-[10px] mb-1">No Telp / WhatsApp</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200 block">{{ $ticket->customer_phone ?? '-' }}</span>
                        @if($ticket->customer_phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ticket->customer_phone) }}" target="_blank" 
                               class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 mt-2 px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold transition-all border border-white/10 focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[44px]">
                                <i class="fa-brands fa-whatsapp text-sm"></i> Hubungi WhatsApp
                            </a>
                        @endif
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200/60 dark:border-white/5">
                        <span class="block font-semibold uppercase text-slate-500 dark:text-slate-400 text-[10px] mb-1">Kategori Gangguan</span>
                        <span class="text-sm font-bold text-indigo-700 dark:text-indigo-300 block">{{ $ticket->category_name ?? 'Umum' }}</span>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200/60 dark:border-white/5 text-xs">
                    <span class="block font-semibold uppercase text-slate-500 dark:text-slate-400 text-[10px] mb-1">Alamat Pelanggan</span>
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $ticket->customer_address ?? 'Tidak ada catatan alamat.' }}</p>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200/60 dark:border-white/5 text-xs">
                    <span class="block font-semibold uppercase text-slate-500 dark:text-slate-400 text-[10px] mb-1">Keterangan / Laporan Masalah</span>
                    <p class="text-slate-800 dark:text-slate-200 leading-relaxed font-medium bg-white/80 dark:bg-slate-950/80 p-3.5 rounded-xl border border-slate-200/80 dark:border-white/5">{{ $ticket->problem_description }}</p>
                </div>

                @if($ticket->latitude && $ticket->longitude)
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between bg-blue-50 dark:bg-blue-950/30 p-4 rounded-2xl border border-blue-200 dark:border-blue-500/30 gap-3">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-2xl text-blue-600 dark:text-blue-400"></i>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white text-xs">Koordinat Lokasi Pelanggan</div>
                                <div class="text-[11px] font-mono text-blue-700 dark:text-blue-300">{{ $ticket->latitude }}, {{ $ticket->longitude }}</div>
                            </div>
                        </div>
                        <a href="https://www.google.com/maps/place/{{ $ticket->latitude }},{{ $ticket->longitude }}" target="_blank"
                           class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-blue-600/25 transition-all flex items-center justify-center gap-1.5 border border-white/10 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px]">
                            <i class="fa-solid fa-map"></i> Buka Google Maps
                        </a>
                    </div>
                @endif
            </div>

            <!-- Timeline History Card -->
            <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm dark:shadow-xl space-y-5 sm:space-y-6 relative overflow-hidden backdrop-blur-sm transition-colors">
                <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2 border-b border-slate-200/80 dark:border-white/10 pb-3">
                    <i class="fa-solid fa-timeline text-indigo-600 dark:text-indigo-400"></i>
                    <span>Timeline Pengerjaan Tiket</span>
                </h3>

                <div class="relative pl-5 sm:pl-6 space-y-5 sm:space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-300 dark:before:bg-slate-700/60">
                    @foreach($ticket->timelines as $timeline)
                        <div class="relative group">
                            <!-- Icon Marker -->
                            <div class="absolute -left-5 sm:-left-6 top-0.5 w-4 h-4 rounded-full bg-blue-500 ring-4 ring-white dark:ring-slate-900 group-hover:scale-125 transition-transform"></div>
                            
                            <div class="bg-slate-50 dark:bg-slate-950/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200/60 dark:border-white/5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs mb-1 gap-1">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ $timeline->user->name ?? 'System' }} 
                                        <span class="text-[10px] text-blue-600 dark:text-blue-400 capitalize font-normal">({{ $timeline->user->role ?? 'Billing Client' }})</span>
                                    </span>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $timeline->created_at->format('d M Y, H:i') }} WIB</span>
                                </div>
                                <div class="text-xs text-slate-700 dark:text-slate-300 mt-1.5 font-medium leading-relaxed">{{ $timeline->remark }}</div>
                                <div class="mt-2">
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-md bg-slate-200/80 dark:bg-slate-900 border border-slate-300 dark:border-white/10 text-slate-700 dark:text-slate-300">
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
        <div class="space-y-5 sm:space-y-6">
            <!-- Assigned Technician Status Box -->
            <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm dark:shadow-xl space-y-4 relative overflow-hidden backdrop-blur-sm transition-colors">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wider flex items-center gap-2 border-b border-slate-200/80 dark:border-white/10 pb-3">
                    <i class="fa-solid fa-user-gear text-emerald-600 dark:text-emerald-400"></i>
                    <span>Teknisi Penanggung Jawab</span>
                </h3>

                @if($ticket->assignedTechnician)
                    <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 space-y-2">
                        <div class="text-base font-bold">{{ $ticket->assignedTechnician->name }}</div>
                        <div class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-2">
                            <i class="fa-solid fa-phone"></i> {{ $ticket->assignedTechnician->phone ?? '-' }}
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-lg text-rose-600 dark:text-rose-400"></i>
                        <span>Belum ditugaskan ke teknisi manapun.</span>
                    </div>
                @endif

                @if($user->role !== 'technician')
                    <!-- Form Assign Technician (Admin / Operator Only) -->
                    <form action="{{ route('tickets.assign', $ticket->id) }}" method="POST" class="pt-4 border-t border-slate-200/80 dark:border-white/10 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300">Tugaskan / Ganti Teknisi</label>
                        <select name="technician_id" required class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                            <option value="">-- Pilih Teknisi --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ $ticket->assigned_technician_id == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->name }} ({{ $tech->phone }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-emerald-600/25 border border-white/10 transition-all flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[44px]">
                            <i class="fa-solid fa-user-check"></i> Simpan Penugasan Teknisi
                        </button>
                    </form>
                @endif
            </div>

            <!-- Form Update Status Tiket -->
            <div class="bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm dark:shadow-xl space-y-4 relative overflow-hidden backdrop-blur-sm transition-colors">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm uppercase tracking-wider flex items-center gap-2 border-b border-slate-200/80 dark:border-white/10 pb-3">
                    <i class="fa-solid fa-pen-to-square text-blue-600 dark:text-blue-400"></i>
                    <span>Update Status & Catatan</span>
                </h3>

                <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Pilih Status Baru</label>
                        <select name="status" required class="w-full px-3 py-2.5 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30 min-h-[44px]">
                            <option value="pending" {{ $ticket->status === 'pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                            <option value="process" {{ $ticket->status === 'process' ? 'selected' : '' }}>Proces (Sedang Dikerjakan)</option>
                            <option value="close" {{ $ticket->status === 'close' ? 'selected' : '' }}>Close (Selesai)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Catatan / Keterangan Penanganan</label>
                        <textarea name="remark" rows="4" required placeholder="Contoh: Kabel FO telah disambung kembali, koneksi sudah aktif normal..."
                                  class="w-full p-3 bg-white dark:bg-slate-950/70 border border-slate-300 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30"></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-blue-600/25 border border-white/10 transition-all flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[44px]">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
