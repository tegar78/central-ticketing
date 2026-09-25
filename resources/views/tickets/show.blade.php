@extends('layouts.app')

@section('title', 'Detail Tiket #' . $ticket->ticket_number)

@section('content')
<div class="space-y-6">
    <!-- Back Header & Status -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white text-xs sm:text-sm font-semibold transition-colors px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 w-fit">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali ke Dashboard</span>
        </a>
        <div class="flex items-center gap-2">
            @if($ticket->status === 'pending')
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-sky-500"></span> Status: Pending
                </span>
            @elseif($ticket->status === 'process')
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-spin"></span> Status: Dalam Proses
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Status: Selesai (Close)
                </span>
            @endif
        </div>
    </div>

    <!-- Main Detail Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Ticket & Customer Info, Timeline -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Info Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5 transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 gap-2">
                    <div>
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Informasi Tiket Gangguan</span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2 mt-0.5">
                            <span>#{{ $ticket->ticket_number }}</span>
                        </h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 px-3 py-1 rounded-lg text-xs font-bold font-mono">
                            {{ $ticket->billingInstance->name ?? 'Billing Gayuh' }}
                        </span>
                        <span class="text-xs text-slate-400">• {{ $ticket->created_at->format('d M Y, H:i') }} WIB</span>
                    </div>
                </div>

                <!-- Customer Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-xs">
                    <div class="bg-slate-50 dark:bg-slate-800/50 p-3.5 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block font-bold uppercase text-slate-400 dark:text-slate-500 text-[10px] mb-1">Nama Pelanggan</span>
                        <span class="text-sm font-bold text-slate-900 dark:text-white block">{{ $ticket->customer_name }}</span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 p-3.5 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block font-bold uppercase text-slate-400 dark:text-slate-500 text-[10px] mb-1">No. Layanan / ID Sub</span>
                        <span class="text-sm font-mono font-bold text-emerald-600 dark:text-emerald-400 block">{{ $ticket->no_services }}</span>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 p-3.5 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block font-bold uppercase text-slate-400 dark:text-slate-500 text-[10px] mb-1">No. Telp / WhatsApp</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200 block">{{ $ticket->customer_phone ?? '-' }}</span>
                        @if($ticket->customer_phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ticket->customer_phone) }}" target="_blank" 
                               class="inline-flex items-center gap-1.5 mt-2 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-sm transition-all">
                                <i class="fa-brands fa-whatsapp"></i> Hubungi via WA
                            </a>
                        @endif
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 p-3.5 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="block font-bold uppercase text-slate-400 dark:text-slate-500 text-[10px] mb-1">Kategori Gangguan</span>
                        <span class="text-sm font-bold text-slate-900 dark:text-white block">{{ $ticket->category_name ?? 'Umum' }}</span>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 p-3.5 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-800 text-xs">
                    <span class="block font-bold uppercase text-slate-400 dark:text-slate-500 text-[10px] mb-1">Alamat Pelanggan</span>
                    <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $ticket->customer_address ?? 'Tidak ada catatan alamat.' }}</p>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 p-3.5 sm:p-4 rounded-xl border border-slate-100 dark:border-slate-800 text-xs">
                    <span class="block font-bold uppercase text-slate-400 dark:text-slate-500 text-[10px] mb-1">Keterangan / Laporan Masalah</span>
                    <p class="text-slate-800 dark:text-slate-200 leading-relaxed font-medium bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200/80 dark:border-slate-800 mt-1">{{ $ticket->problem_description }}</p>
                </div>

                @if($ticket->latitude && $ticket->longitude)
                    <div class="flex items-center justify-between bg-emerald-50/50 dark:bg-emerald-950/20 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/60">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-xl text-emerald-600 dark:text-emerald-400"></i>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white text-xs">Titik Koordinat Lokasi</div>
                                <div class="text-[11px] font-mono text-emerald-700 dark:text-emerald-300">{{ $ticket->latitude }}, {{ $ticket->longitude }}</div>
                            </div>
                        </div>
                        <a href="https://www.google.com/maps/place/{{ $ticket->latitude }},{{ $ticket->longitude }}" target="_blank"
                           class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-map"></i> Buka Maps
                        </a>
                    </div>
                @endif
            </div>

            <!-- Timeline History Card (Figma Style) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                        <i class="fa-solid fa-timeline text-emerald-600 dark:text-emerald-400"></i>
                        <span>Timeline Pengerjaan Tiket</span>
                    </h3>
                    <span class="text-xs text-slate-400">{{ count($ticket->timelines) }} Catatan</span>
                </div>

                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                    @forelse($ticket->timelines as $timeline)
                        <div class="relative group">
                            <!-- Dot Indicator -->
                            <div class="absolute -left-6 top-1 w-4 h-4 rounded-full bg-white dark:bg-slate-900 border-2 border-emerald-500 flex items-center justify-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            </div>
                            
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs mb-1.5 gap-1">
                                    <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        {{ $timeline->user->name ?? 'System Central' }} 
                                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/40 uppercase">
                                            {{ $timeline->user->role ?? 'System' }}
                                        </span>
                                    </span>
                                    <span class="text-[11px] text-slate-400">{{ $timeline->created_at->format('d M Y, H:i') }} WIB</span>
                                </div>
                                <div class="text-xs text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{ $timeline->remark }}</div>
                                <div class="mt-2.5">
                                    @if($timeline->status === 'pending')
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60 uppercase">
                                            Status: Pending
                                        </span>
                                    @elseif($timeline->status === 'process')
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60 uppercase">
                                            Status: Proses
                                        </span>
                                    @else
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 uppercase">
                                            Status: Close
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">Belum ada riwayat aktivitas pada tiket ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Actions Panel (Teknisi & Status) -->
        <div class="space-y-6">
            <!-- Assigned Technician Box -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4 transition-colors">
                <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <i class="fa-solid fa-user-gear text-emerald-600 dark:text-emerald-400"></i>
                    <span>Teknisi Penanggung Jawab</span>
                </h3>

                @if($ticket->assignedTechnician)
                    <div class="p-4 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60">
                        <div class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $ticket->assignedTechnician->name }}</div>
                        <div class="text-xs text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5 mt-1 font-medium">
                            <i class="fa-solid fa-phone text-[10px]"></i> {{ $ticket->assignedTechnician->phone ?? '-' }}
                        </div>
                    </div>
                @else
                    <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs font-medium flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm"></i>
                        <span>Belum ditugaskan ke teknisi.</span>
                    </div>
                @endif

                @if(!$ticket->isClosed() && $user->role !== 'technician')
                    <!-- Form Assign Technician -->
                    <form action="{{ route('tickets.assign', $ticket->id) }}" method="POST" class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        @csrf
                        <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400">Tugaskan / Ganti Teknisi</label>
                        <select name="technician_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                            <option value="">-- Pilih Teknisi --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ $ticket->assigned_technician_id == $tech->id ? 'selected' : '' }}>
                                    {{ $tech->name }} ({{ $tech->phone ?? 'Tanpa WA' }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full py-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-user-check"></i> Simpan Penugasan
                        </button>
                    </form>
                @elseif($ticket->isClosed())
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-xl">
                        <i class="fa-solid fa-lock text-slate-400 text-xs"></i>
                        <span>Penugasan terkunci karena tiket telah selesai.</span>
                    </div>
                @endif
            </div>

            @if($ticket->isClosed())
                <!-- Locked State Card: Tiket Selesai (Closed) -->
                <div class="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-800/50 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4 transition-colors">
                    <div class="flex items-center gap-2.5 border-b border-emerald-200/60 dark:border-emerald-800/40 pb-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-sm shadow-sm">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-emerald-900 dark:text-emerald-300 text-sm">
                                Tiket Selesai & Terkunci
                            </h3>
                            <span class="text-[11px] text-emerald-700 dark:text-emerald-400 font-medium">
                                Status Closed Permanen
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Seluruh tindakan penanganan gangguan pada tiket ini telah diselesaikan dan diarsipkan. Status dan catatan penanganan tidak dapat diperbarui lagi.
                    </p>

                    @if($ticket->closed_at)
                    <div class="p-3.5 bg-white/80 dark:bg-slate-900/80 rounded-xl border border-emerald-100 dark:border-emerald-900/40 text-xs space-y-1.5">
                        <div class="text-[10px] font-bold uppercase text-slate-400">Waktu Penyelesaian:</div>
                        <div class="font-semibold text-slate-800 dark:text-slate-200 font-mono text-xs">
                            {{ $ticket->closed_at->format('d M Y - H:i:s') }} WIB
                        </div>
                        @if($ticket->action_remark && $ticket->action_remark !== '-')
                        <div class="text-[10px] font-bold uppercase text-slate-400 pt-1.5 mt-1.5 border-t border-slate-100 dark:border-slate-800">Catatan Akhir:</div>
                        <div class="text-slate-700 dark:text-slate-300 italic text-[11px]">
                            "{{ $ticket->action_remark }}"
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            @else
                <!-- Form Update Status & Remark Box (Hanya Jika Belum Closed) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4 transition-colors">
                    <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <i class="fa-solid fa-pen-to-square text-emerald-600 dark:text-emerald-400"></i>
                        <span>Perbarui Status Tiket</span>
                    </h3>

                    <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Status Baru</label>
                            <select name="status" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                                <option value="pending" {{ $ticket->status === 'pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                                <option value="process" {{ $ticket->status === 'process' ? 'selected' : '' }}>Process (Sedang Dikerjakan)</option>
                                <option value="close" {{ $ticket->status === 'close' ? 'selected' : '' }}>Close (Selesai)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Catatan Penanganan</label>
                            <textarea name="remark" rows="3" required placeholder="Contoh: Kabel FO telah disambung kembali..."
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500"></textarea>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan & Sinkronkan ke Billing
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
