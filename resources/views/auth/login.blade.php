<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Central Ticket System</title>
    <!-- Prevent FOUC: Apply dark or light class immediately -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden selection:bg-blue-500/30 selection:text-blue-200 transition-colors duration-200">
    <!-- Ambient Lighting for Glass Refraction -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-20 w-96 h-96 bg-blue-400/20 dark:bg-blue-600/15 rounded-full blur-3xl transition-colors"></div>
        <div class="absolute -bottom-32 -right-20 w-96 h-96 bg-indigo-400/20 dark:bg-indigo-600/15 rounded-full blur-3xl transition-colors"></div>
    </div>

    <!-- Theme Toggle Floating (R-21) -->
    <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-20">
        <button type="button" onclick="toggleTheme()" class="w-11 h-11 rounded-xl bg-white/80 hover:bg-white text-slate-700 dark:bg-slate-900/80 dark:hover:bg-slate-800 dark:text-amber-300 border border-slate-200 dark:border-white/10 shadow-sm transition-all flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-400" title="Ganti Mode Terang atau Gelap" aria-label="Ganti mode tampilan">
            <i class="fa-solid fa-moon text-sm block dark:hidden"></i>
            <i class="fa-solid fa-sun text-sm hidden dark:block"></i>
        </button>
    </div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo Header -->
        <div class="text-center mb-6 sm:mb-8">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 mx-auto flex items-center justify-center text-white text-xl sm:text-2xl shadow-xl shadow-blue-500/25 border border-white/20 mb-3 sm:mb-4">
                <i class="fa-solid fa-headset"></i>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Central Ticket System</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Multi-Tenant Management untuk 60 Billing App</p>
        </div>

        <!-- Login Glass Card (R-10 Primary Glass Accent) -->
        <div class="bg-white/85 dark:bg-slate-900/80 border border-slate-200/80 dark:border-white/10 rounded-3xl p-5 sm:p-8 shadow-xl dark:shadow-2xl backdrop-blur-md relative overflow-hidden transition-colors">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/20 dark:via-white/20 to-transparent"></div>

            @if($errors->any())
                <div class="mb-5 sm:mb-6 p-3.5 sm:p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs sm:text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-base sm:text-lg text-rose-500 dark:text-rose-400 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-4 sm:space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full min-h-[48px] pl-10 pr-4 py-3 bg-white dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700/80 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-400/20 dark:focus:ring-blue-400/30 transition-all text-sm"
                            placeholder="admin@central.local">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-700 dark:text-slate-300 tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full min-h-[48px] pl-10 pr-4 py-3 bg-white dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700/80 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-400/20 dark:focus:ring-blue-400/30 transition-all text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                    <label class="min-h-[44px] flex items-center gap-2 cursor-pointer py-1">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-white dark:bg-slate-950/60 border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full min-h-[48px] py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center justify-center gap-2 border border-white/10 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <span>Masuk Portal Tiket</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Sample Credentials Helper -->
            <div class="mt-6 sm:mt-8 pt-5 sm:pt-6 border-t border-slate-200 dark:border-white/5 text-xs text-slate-500 dark:text-slate-400">
                <div class="font-semibold text-slate-700 dark:text-slate-300 mb-2">Akun Testing Terdaftar:</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                    <div class="bg-slate-50 dark:bg-slate-950/40 p-2.5 rounded-xl border border-slate-200 dark:border-white/5">
                        <span class="block font-bold text-blue-600 dark:text-blue-400">Admin/Operator</span>
                        <span class="text-slate-700 dark:text-slate-300">admin@central.local</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-950/40 p-2.5 rounded-xl border border-slate-200 dark:border-white/5">
                        <span class="block font-bold text-emerald-600 dark:text-emerald-400">Teknisi A</span>
                        <span class="text-slate-700 dark:text-slate-300">teknisi1@central.local</span>
                    </div>
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 text-center">Password semua akun: <code class="text-slate-700 dark:text-slate-300 font-mono">password</code></div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Script -->
    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>
</body>
</html>
