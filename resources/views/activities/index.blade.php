@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
        <div>
            <div class="flex items-center gap-2 mb-2 text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors flex items-center gap-1 font-medium">
                    <i class="fa-solid fa-chart-pie text-[11px]"></i>
                    <span>Dashboard</span>
                </a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-300 dark:text-slate-600"></i>
                <span class="font-semibold text-slate-700 dark:text-slate-200">Aktivitas</span>
            </div>

            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-clock-rotate-left text-[#f47b20]"></i>
                <span>Semua Aktivitas Sistem & Tiket</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                Rekam jejak audit trail seluruh tindakan pengguna, pembuatan tiket, penugasan teknisi, dan pembaruan status pengerjaan.
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm rounded-xl transition-all border border-slate-200/60 dark:border-slate-700">
                <i class="fa-solid fa-chart-pie text-xs"></i>
                <span>Kembali ke Dashboard</span>
            </a>
            <a href="{{ route('tickets.index') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm rounded-xl shadow-md shadow-emerald-600/25 transition-all hover:scale-[1.02]">
                <i class="fa-solid fa-list-check text-xs"></i>
                <span>Direktori Tiket</span>
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm transition-colors">
        <form action="{{ route('activities.index') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Cari aktivitas, nama user, remark, no tiket..."
                    class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-400/30 transition-all">
            </div>

            <div class="flex items-center gap-2">
                @if(request('search'))
                <a href="{{ route('activities.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all text-center">
                    Reset
                </a>
                @endif
                <button type="submit" class="px-5 py-2 bg-[#f47b20] hover:bg-orange-600 text-white text-xs font-semibold rounded-xl shadow-md shadow-orange-500/20 transition-all text-center">
                    Cari
                </button>
            </div>
        </form>
    </div>

    <!-- Card Tabel Semua Aktivitas (Clean Minimalist Style) -->
    <div class="bg-[#edf2f7]/80 dark:bg-slate-900 border border-[#d7e2ec] dark:border-slate-800 p-5 sm:p-7 rounded-2xl shadow-sm transition-colors">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-[#f47b20] dark:text-orange-400">
                DAFTAR SEMUA AKTIVITAS ({{ $activities->total() }})
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="text-[#596d84] dark:text-slate-400 font-semibold border-b border-[#cfdbe8] dark:border-slate-800">
                        <th class="py-3 px-3 w-14 text-center">No</th>
                        <th class="py-3 px-4 w-32">Name</th>
                        <th class="py-3 px-4 w-32">Level</th>
                        <th class="py-3 px-4 w-52">Time</th>
                        <th class="py-3 px-4">Remark</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#dbe5f0] dark:divide-slate-800/80 text-xs sm:text-sm">
                    @forelse($activities as $index => $act)
                    <tr class="hover:bg-white/40 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="py-3 px-3 text-center text-[#334155] dark:text-slate-300 font-medium">
                            {{ $activities->firstItem() + $index }}
                        </td>
                        <td class="py-3 px-4 text-[#1e293b] dark:text-slate-200 font-medium whitespace-nowrap">
                            {{ $act->user->name ?? 'System' }}
                        </td>
                        <td class="py-3 px-4 text-[#334155] dark:text-slate-300 whitespace-nowrap">
                            {{ ucfirst($act->user->role ?? 'User') }}
                        </td>
                        <td class="py-3 px-4 text-[#334155] dark:text-slate-300 whitespace-nowrap font-mono text-xs">
                            {{ $act->created_at->format('d-M-Y - H:i:s') }}
                        </td>
                        <td class="py-3 px-4 text-[#334155] dark:text-slate-300">
                            @if($act->ticket)
                            <a href="{{ route('tickets.show', $act->ticket->id) }}" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline mr-1">
                                [{{ $act->ticket->ticket_number }}]
                            </a>
                            @endif
                            {{ $act->remark ?: 'Aktivitas tiket' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-clock-rotate-left text-3xl mb-2 text-slate-300 dark:text-slate-600 block"></i>
                            <span class="font-medium">Tidak ada catatan aktivitas yang ditemukan.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
        <div class="pt-5 mt-4 border-t border-slate-100 dark:border-slate-800">
            {{ $activities->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
