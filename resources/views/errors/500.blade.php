<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Server | {{ config('app.name', 'MANAGEMENT TICKET') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-[#F8F9FA] dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center p-4 transition-colors duration-200">
    <div class="max-w-md w-full bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-8 shadow-xl text-center space-y-6">
        <div class="w-20 h-20 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-500 flex items-center justify-center mx-auto text-3xl shadow-sm border border-rose-200/60 dark:border-rose-800/40">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        
        <div>
            <span class="text-xs font-bold font-mono uppercase tracking-widest text-rose-600 dark:text-rose-400">Error 500</span>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white mt-1">Kesalahan Internal Server</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                Terjadi kendala pada sistem saat memproses permintaan Anda. Tim teknis telah mencatat log aktivitas ini.
            </p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition-all shadow-md shadow-emerald-600/20">
                <i class="fa-solid fa-rotate-right text-xs"></i>
                <span>Coba Muat Ulang</span>
            </a>
            <a href="javascript:history.back()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs transition-all">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>
</body>
</html>
