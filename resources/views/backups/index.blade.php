@extends('layouts.app')

@section('title', 'Backup Database')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden transition-colors">
        <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-transparent"></div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 flex-shrink-0">
                    <i class="fa-solid fa-database text-base"></i>
                </div>
                <span>Backup & Recovery Database</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Pencadangan database MySQL / MariaDB lokal secara aman dan terenkapsulasi.
            </p>
        </div>

        <!-- Tombol Aksi Backup Cepat -->
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="document.getElementById('createBackupModal').classList.remove('hidden')" 
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-sm shadow-emerald-600/20 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400 min-h-[42px]">
                <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                <span>Backup Sekarang</span>
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300 animate-fade-in">
        <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-xl flex items-center justify-between text-xs text-rose-800 dark:text-rose-300 animate-fade-in">
        <div class="flex items-center gap-2.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600 dark:text-rose-400 text-sm"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    <!-- Stat Cards (4 Columns) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total File Backup -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 rounded-2xl shadow-sm relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all">
            <div class="absolute -right-3 -bottom-3 text-slate-200/50 dark:text-slate-800/40 text-5xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <div class="text-[11px] font-semibold uppercase text-slate-400 dark:text-slate-500 tracking-wider">Total File</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($totalCount) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">Arsip tersimpan</div>
        </div>

        <!-- Total Ukuran Disk -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 rounded-2xl shadow-sm relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all">
            <div class="absolute -right-3 -bottom-3 text-emerald-500/10 text-5xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-hard-drive"></i>
            </div>
            <div class="text-[11px] font-semibold uppercase text-emerald-600 dark:text-emerald-400 tracking-wider">Kapasitas Disk</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $totalSizeHuman }}</div>
            <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">Total terpakai</div>
        </div>

        <!-- Database Engine -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 rounded-2xl shadow-sm relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all">
            <div class="absolute -right-3 -bottom-3 text-indigo-500/10 text-5xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-server"></i>
            </div>
            <div class="text-[11px] font-semibold uppercase text-indigo-600 dark:text-indigo-400 tracking-wider">Engine Database</div>
            <div class="text-lg font-bold text-slate-900 dark:text-white mt-1 truncate">{{ $dbName }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ $dbDriver }} ({{ $tableCount }} Tabel)</div>
        </div>

        <!-- Backup Terakhir -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 rounded-2xl shadow-sm relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all">
            <div class="absolute -right-3 -bottom-3 text-amber-500/10 text-5xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="text-[11px] font-semibold uppercase text-amber-600 dark:text-amber-400 tracking-wider">Backup Terakhir</div>
            <div class="text-base font-bold text-slate-900 dark:text-white mt-1 truncate">
                {{ $latestBackup ? $latestBackup['created_human'] : 'Belum Ada' }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $latestBackup ? $latestBackup['created_at'] : '-' }}
            </div>
        </div>
    </div>

    <!-- Retention Tool Bar & Info -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fa-solid fa-broom"></i>
            </div>
            <div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">Pembersihan Otomatis (Auto-Retention)</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Hapus file backup lama untuk menjaga kelegaan kapasitas disk server.</p>
            </div>
        </div>

        <form action="{{ route('backups.clean') }}" method="POST" class="flex items-center gap-2" onsubmit="return confirm('Apakah Anda yakin ingin menghapus backup yang lebih lama dari jumlah hari ini?');">
            @csrf
            <select name="days" class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                <option value="7">Lebih dari 7 Hari</option>
                <option value="14" selected>Lebih dari 14 Hari</option>
                <option value="30">Lebih dari 30 Hari</option>
                <option value="60">Lebih dari 60 Hari</option>
            </select>
            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 transition-colors">
                <i class="fa-solid fa-trash-can mr-1"></i> Bersihkan
            </button>
        </form>
    </div>

    <!-- Table of Backups -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-emerald-500"></i>
                <span>Riwayat Arsip Backup (storage/app/backups)</span>
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ count($backups) }} File Tersedia</span>
        </div>

        @if(count($backups) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-800">
                        <th class="py-3 px-4">Nama File</th>
                        <th class="py-3 px-4">Tipe Format</th>
                        <th class="py-3 px-4">Ukuran</th>
                        <th class="py-3 px-4">Waktu Dibuat</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($backups as $b)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg {{ $b['is_compressed'] ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' }} flex items-center justify-center flex-shrink-0 text-xs">
                                    <i class="fa-solid {{ $b['is_compressed'] ? 'fa-file-zipper' : 'fa-file-code' }}"></i>
                                </div>
                                <span class="font-mono font-medium text-slate-800 dark:text-slate-200">{{ $b['filename'] }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @if($b['is_compressed'])
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/40">
                                <i class="fa-solid fa-file-zipper text-[9px]"></i> GZIP (.sql.gz)
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
                                <i class="fa-solid fa-file-lines text-[9px]"></i> RAW (.sql)
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono font-semibold text-slate-700 dark:text-slate-300">
                            {{ $b['human_size'] }}
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                            <div>{{ $b['created_at'] }}</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ $b['created_human'] }}</div>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('backups.download', $b['filename']) }}" 
                                   title="Unduh File Backup"
                                   class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60 rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors">
                                    <i class="fa-solid fa-download text-xs"></i>
                                    <span>Unduh</span>
                                </a>

                                <form action="{{ route('backups.destroy', $b['filename']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus backup {{ $b['filename'] }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            title="Hapus File Backup"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition-colors">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <!-- Empty State -->
        <div class="p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center text-2xl mx-auto mb-3">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Belum Ada File Backup</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                Klik tombol "Backup Sekarang" di atas atau jalankan perintah CLI untuk membuat arsip cadangan pertama Anda.
            </p>
        </div>
        @endif
    </div>
</div>

<!-- Modal Dialog Buat Backup Baru -->
<div id="createBackupModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-5 sm:p-6 animate-modal">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Buat Backup Database Baru</h3>
            </div>
            <button type="button" onclick="document.getElementById('createBackupModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="{{ route('backups.create') }}" method="POST" class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerHTML = '<i class=\'fa-solid fa-circle-notch fa-spin mr-1\'></i> Memproses Backup...';">
            @csrf
            
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/60 dark:border-slate-700/60 space-y-3">
                <div class="text-xs text-slate-700 dark:text-slate-300">
                    <span class="font-semibold">Target Database:</span> <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ $dbName }}</span> ({{ $dbDriver }})
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-800 dark:text-slate-200">
                    <input type="checkbox" name="gzip" value="1" checked class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                    <div>
                        <span class="font-semibold">Gunakan Kompresi Gzip (.sql.gz)</span>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Dianjurkan. Menghemat ruang harddisk server hingga 80%.</p>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer text-xs text-slate-800 dark:text-slate-200">
                    <input type="checkbox" name="notify" value="1" class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                    <div>
                        <span class="font-semibold">Kirim Laporan ke Telegram</span>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Kirim pemberitahuan otomatis ke grup Telegram terdaftar.</p>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" onclick="document.getElementById('createBackupModal').classList.add('hidden')" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-play text-[10px]"></i>
                    <span>Mulai Backup</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
