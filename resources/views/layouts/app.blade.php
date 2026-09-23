<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'MANAGEMENT TICKET') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <!-- Prevent FOUC: Apply dark or light class immediately -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN for Analytics & Volumes -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981', // Figma primary emerald
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="bg-[#F8F9FA] dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen antialiased flex selection:bg-emerald-500/20 selection:text-emerald-700 transition-colors duration-200">

    @auth
    <!-- Desktop Left Sidebar (Figma Reference: Ticket Support) -->
    <aside class="hidden lg:flex flex-col w-64 xl:w-72 bg-white dark:bg-slate-900 border-r border-slate-200/90 dark:border-slate-800 shrink-0 sticky top-0 h-screen z-40 select-none">
        <!-- Sidebar Brand Logo -->
        <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-100 dark:border-slate-800/80">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-orange-400 rounded-xl py-1">
                <img src="{{ asset('images/logo.png') }}" alt="MANAGEMENT TICKET" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform drop-shadow-sm">
                <div>
                    <span class="font-black text-sm text-slate-900 dark:text-white tracking-tight leading-tight block uppercase">MANAGEMENT</span>
                    <span class="text-[10px] block text-orange-500 dark:text-orange-400 font-black tracking-widest uppercase">TICKET</span>
                </div>
            </a>
        </div>

        <!-- Sidebar Menu Items -->
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Main Menu Section -->
            <div>
                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Main Menu</div>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') && !request()->has('status') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}">
                        <i class="fa-solid fa-chart-pie text-base {{ request()->routeIs('dashboard') && !request()->has('status') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }} w-5 text-center"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('maps.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('maps.*') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}">
                        <i class="fa-solid fa-map-location-dot text-base {{ request()->routeIs('maps.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }} w-5 text-center"></i>
                        <span>Maps Pelanggan</span>
                    </a>

                    @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <a href="{{ route('customers.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('customers.*') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}">
                        <i class="fa-solid fa-users text-base {{ request()->routeIs('customers.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }} w-5 text-center"></i>
                        <span>Data Pelanggan</span>
                    </a>
                    @endif

                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('users.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('users.*') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50' }}">
                        <i class="fa-solid fa-user-gear text-base {{ request()->routeIs('users.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }} w-5 text-center"></i>
                        <span>Kelola User</span>
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Quick Ticket Status Filter Section -->
            <div>
                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-2">Status Tiket</div>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard', ['status' => 'pending']) }}" 
                       class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-medium transition-all {{ request('status') === 'pending' ? 'bg-sky-50 text-sky-700 font-bold dark:bg-sky-500/10 dark:text-sky-300' : 'text-slate-600 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:bg-slate-800/50' }}">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            <span>Pending (Baru)</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                    </a>

                    <a href="{{ route('dashboard', ['status' => 'process']) }}" 
                       class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-medium transition-all {{ request('status') === 'process' ? 'bg-amber-50 text-amber-700 font-bold dark:bg-amber-500/10 dark:text-amber-300' : 'text-slate-600 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:bg-slate-800/50' }}">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Dalam Proses</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                    </a>

                    <a href="{{ route('dashboard', ['status' => 'close']) }}" 
                       class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-medium transition-all {{ request('status') === 'close' ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-300' : 'text-slate-600 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:bg-slate-800/50' }}">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Selesai (Close)</span>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Sidebar User Profile Footer -->
        <div class="p-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-600 text-white font-bold text-sm flex items-center justify-center shrink-0 shadow-sm">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold capitalize">{{ auth()->user()->role }}</div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Keluar" class="w-8 h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    @endauth

    <!-- Main Content Area & Top Header -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
        <!-- Top Header Bar -->
        <header class="sticky top-0 z-30 h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-4 sm:px-6 lg:px-8 flex items-center justify-between transition-colors">
            <!-- Left Header: Mobile Toggle & Page Title / Breadcrumb -->
            <div class="flex items-center gap-3">
                @auth
                <!-- Mobile Hamburger Button -->
                <button type="button" onclick="toggleMobileMenu()" id="mobileMenuBtn" class="lg:hidden w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 flex items-center justify-center transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400" aria-label="Buka menu navigasi">
                    <i class="fa-solid fa-bars text-sm" id="mobileMenuIconOpen"></i>
                    <i class="fa-solid fa-xmark text-sm hidden" id="mobileMenuIconClose"></i>
                </button>
                @endauth

                <!-- Breadcrumb / Page Title -->
                <div class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500 font-medium">
                    <span class="hidden sm:inline">Dashboard</span>
                    <span class="hidden sm:inline">/</span>
                    <span class="font-bold text-slate-800 dark:text-slate-100 text-sm sm:text-base">
                        @if(request()->routeIs('dashboard'))
                            Ticket Overview
                        @elseif(request()->routeIs('maps.*'))
                            Maps Location Pelanggan
                        @elseif(request()->routeIs('customers.*'))
                            Data Pelanggan
                        @elseif(request()->routeIs('users.*'))
                            Kelola Pengguna
                        @elseif(request()->routeIs('tickets.show'))
                            Detail Tiket
                        @else
                            {{ config('app.name') }}
                        @endif
                    </span>
                </div>
            </div>

            <!-- Right Header: Quick Search, Controls & CTA Button -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <!-- Search Input Bar (Desktop) -->
                <form action="{{ route('dashboard') }}" method="GET" class="hidden md:flex items-center relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari tiket, pelanggan..." 
                           class="w-56 lg:w-64 pl-8 pr-8 py-1.5 bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition-all">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                        <kbd class="text-[10px] font-mono bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-1.5 py-0.5 rounded">Ctrl K</kbd>
                    </span>
                </form>

                <!-- Fullscreen Toggle Button -->
                <button type="button" onclick="toggleFullscreen()" id="fullscreenToggleBtn" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 transition-all flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-emerald-400" title="Layar Penuh">
                    <i class="fa-solid fa-expand text-xs" id="fullscreenIcon"></i>
                </button>

                <!-- Light / Dark Mode Toggle Button -->
                <button type="button" onclick="toggleTheme()" id="themeToggleBtn" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-amber-400 transition-all flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-emerald-400" title="Ganti Mode Tampilan">
                    <i class="fa-solid fa-moon text-xs block dark:hidden"></i>
                    <i class="fa-solid fa-sun text-xs hidden dark:block"></i>
                </button>


            </div>
        </header>

        <!-- Mobile Drawer Navigation -->
        @auth
        <div id="mobileMenuDrawer" class="hidden lg:hidden border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl px-4 py-4 space-y-4 transition-all z-30">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-100 dark:bg-slate-800">
                <div class="w-9 h-9 rounded-full bg-emerald-600 text-white font-bold text-sm flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 capitalize">Role: {{ auth()->user()->role }}</div>
                </div>
            </div>

            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium {{ request()->routeIs('dashboard') && !request()->has('status') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300' }}">
                    <i class="fa-solid fa-chart-pie w-5 text-center text-emerald-500"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('maps.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium {{ request()->routeIs('maps.*') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300' }}">
                    <i class="fa-solid fa-map-location-dot w-5 text-center text-emerald-500"></i>
                    <span>Maps Pelanggan</span>
                </a>
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium {{ request()->routeIs('customers.*') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300' }}">
                    <i class="fa-solid fa-users w-5 text-center text-emerald-500"></i>
                    <span>Data Pelanggan</span>
                </a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium {{ request()->routeIs('users.*') ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300' }}">
                    <i class="fa-solid fa-user-gear w-5 text-center text-emerald-500"></i>
                    <span>Kelola User</span>
                </a>
                @endif

                <!-- Quick Ticket Status Filter in Mobile Drawer -->
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                    <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 mb-1.5">Status Tiket</div>
                    <div class="space-y-1">
                        <a href="{{ route('dashboard', ['status' => 'pending']) }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request('status') === 'pending' ? 'bg-sky-50 text-sky-700 font-bold dark:bg-sky-500/10 dark:text-sky-300' : 'text-slate-700 dark:text-slate-300' }}">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                <span>Pending (Baru)</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                        </a>
                        <a href="{{ route('dashboard', ['status' => 'process']) }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request('status') === 'process' ? 'bg-amber-50 text-amber-700 font-bold dark:bg-amber-500/10 dark:text-amber-300' : 'text-slate-700 dark:text-slate-300' }}">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Dalam Proses</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                        </a>
                        <a href="{{ route('dashboard', ['status' => 'close']) }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all {{ request('status') === 'close' ? 'bg-emerald-50 text-emerald-700 font-bold dark:bg-emerald-500/10 dark:text-emerald-300' : 'text-slate-700 dark:text-slate-300' }}">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Selesai (Close)</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                        </a>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Keluar dari Sistem</span>
                        </button>
                    </form>
                </div>
            </nav>
        </div>
        @endauth

        <!-- Main Body Content -->
        <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-6">
            @if(session('success'))
                <div class="mb-5 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-lg text-emerald-600 dark:text-emerald-400"></i>
                        <span class="text-xs sm:text-sm font-semibold">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="w-7 h-7 flex items-center justify-center text-emerald-600 dark:text-emerald-400 hover:text-emerald-800"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-lg text-rose-600 dark:text-rose-400"></i>
                        <span class="text-xs sm:text-sm font-semibold">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="w-7 h-7 flex items-center justify-center text-rose-600 dark:text-rose-400 hover:text-rose-800"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200/80 dark:border-slate-800/80 bg-white/50 dark:bg-slate-900/50 py-4 px-6 text-center text-xs text-slate-400 dark:text-slate-500">
            &copy; {{ date('Y') }} MANAGEMENT TICKET. Terhubung dengan Billing Gayuh.
        </footer>
    </div>

    <!-- Scripts -->
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

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        document.addEventListener('fullscreenchange', () => {
            const icon = document.getElementById('fullscreenIcon');
            if (icon) {
                if (document.fullscreenElement) {
                    icon.classList.remove('fa-expand');
                    icon.classList.add('fa-compress');
                } else {
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            }
        });

        function toggleMobileMenu() {
            const drawer = document.getElementById('mobileMenuDrawer');
            const iconOpen = document.getElementById('mobileMenuIconOpen');
            const iconClose = document.getElementById('mobileMenuIconClose');

            if (!drawer) return;

            if (drawer.classList.contains('hidden')) {
                drawer.classList.remove('hidden');
                if (iconOpen) iconOpen.classList.add('hidden');
                if (iconClose) iconClose.classList.remove('hidden');
            } else {
                drawer.classList.add('hidden');
                if (iconOpen) iconOpen.classList.remove('hidden');
                if (iconClose) iconClose.classList.add('hidden');
            }
        }

        // Global shortcut Ctrl+K to focus search
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) searchInput.focus();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
