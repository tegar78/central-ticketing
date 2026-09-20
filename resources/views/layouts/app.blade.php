<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Central Ticket System') }}</title>
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
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100 min-h-screen flex flex-col relative selection:bg-blue-500/30 selection:text-blue-200 transition-colors duration-200 overflow-x-hidden">
    <!-- Ambient Lighting for Glass Refraction (subtle, restrained per R-01) -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-40 left-1/4 w-96 h-96 bg-blue-400/20 dark:bg-blue-600/10 rounded-full blur-3xl transition-colors"></div>
        <div class="absolute top-1/3 -right-20 w-80 h-80 bg-indigo-400/20 dark:bg-indigo-600/10 rounded-full blur-3xl transition-colors"></div>
    </div>

    <!-- Frosted Glass Navbar (R-10 Primary Glass Accent 1) -->
    <nav class="bg-white/80 dark:bg-slate-900/75 backdrop-blur-md border-b border-slate-200/80 dark:border-white/10 sticky top-0 z-50 shadow-sm dark:shadow-lg dark:shadow-black/20 transition-colors">
        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
            <div class="flex items-center justify-between h-16">
                <!-- Brand & Desktop Nav Links -->
                <div class="flex items-center gap-4 sm:gap-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 sm:gap-3 group focus:outline-none focus:ring-2 focus:ring-blue-400 rounded-xl py-1">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white border border-white/20 shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-headset text-base sm:text-lg"></i>
                        </div>
                        <div>
                            <span class="font-bold text-base sm:text-lg text-slate-900 dark:text-white tracking-tight leading-tight block">Central Ticket</span>
                            <span class="text-[10px] sm:text-xs block text-blue-600 dark:text-blue-400 font-medium">60 Billing Portal</span>
                        </div>
                    </a>

                    @auth
                    <div class="hidden md:flex items-center gap-2 ml-4">
                        <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm dark:bg-blue-600/25 dark:text-blue-300 dark:border-blue-400/30 font-semibold' : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-white border border-transparent' }}">
                            <i class="fa-solid fa-chart-line mr-1.5"></i> Dashboard
                        </a>
                        @if(in_array(auth()->user()->role, ['admin', 'operator']))
                        <a href="{{ route('customers.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 {{ request()->routeIs('customers.*') ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm dark:bg-blue-600/25 dark:text-blue-300 dark:border-blue-400/30 font-semibold' : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-white border border-transparent' }}">
                            <i class="fa-solid fa-address-book mr-1.5"></i> Data Pelanggan
                        </a>
                        <a href="{{ route('users.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-medium transition-all focus:outline-none focus:ring-2 focus:ring-blue-400 {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-sm dark:bg-blue-600/25 dark:text-blue-300 dark:border-blue-400/30 font-semibold' : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-white border border-transparent' }}">
                            <i class="fa-solid fa-users mr-1.5"></i> Kelola User
                        </a>
                        @endif
                    </div>
                    @endauth
                </div>

                <!-- Right Nav Controls (Theme Toggle, Fullscreen Toggle, User Profile, Mobile Hamburger) -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Fullscreen Toggle Button (Desktop >= sm, 44x44px touch area) -->
                    <button type="button" onclick="toggleFullscreen()" id="fullscreenToggleBtn" class="hidden sm:flex w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-slate-200/80 hover:bg-slate-300/80 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 border border-slate-300/80 dark:border-white/10 transition-all items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-400" title="Layar Penuh (Fullscreen)" aria-label="Layar penuh">
                        <i class="fa-solid fa-expand text-sm" id="fullscreenIcon"></i>
                    </button>

                    <!-- Light / Dark Mode Toggle Button (R-21, 44x44px touch area) -->
                    <button type="button" onclick="toggleTheme()" id="themeToggleBtn" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-slate-200/80 hover:bg-slate-300/80 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-amber-300 border border-slate-300/80 dark:border-white/10 transition-all flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-400" title="Ganti Mode Terang atau Gelap" aria-label="Ganti mode tampilan">
                        <i class="fa-solid fa-moon text-sm block dark:hidden"></i>
                        <i class="fa-solid fa-sun text-sm hidden dark:block"></i>
                    </button>

                    @auth
                    <!-- Desktop User Profile Pill -->
                    <div class="hidden sm:flex items-center gap-3 px-3 py-1.5 rounded-xl bg-slate-200/60 dark:bg-slate-800/70 border border-slate-300/60 dark:border-white/10 shadow-sm">
                        <div class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-700 dark:bg-blue-600/30 dark:text-blue-300 flex items-center justify-center font-bold text-sm border border-blue-500/20 dark:border-blue-400/30">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="text-left">
                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-100 leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 capitalize">
                                Role: <span class="text-blue-600 dark:text-blue-400 font-medium">{{ auth()->user()->role }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Logout Button (44x44px touch area) -->
                    <form action="{{ route('logout') }}" method="POST" class="hidden sm:inline">
                        @csrf
                        <button type="submit" title="Keluar dari sistem" class="w-10 h-10 sm:w-11 sm:h-11 text-slate-500 hover:text-rose-600 hover:bg-rose-500/10 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-rose-500/10 rounded-xl transition-all border border-transparent hover:border-rose-500/20 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-rose-400" aria-label="Keluar">
                            <i class="fa-solid fa-right-from-bracket text-sm"></i>
                        </button>
                    </form>

                    <!-- Mobile Menu Hamburger Button (44x44px touch target, antislop R-03) -->
                    <button type="button" onclick="toggleMobileMenu()" id="mobileMenuBtn" class="md:hidden w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-slate-200/80 hover:bg-slate-300/80 text-slate-700 dark:bg-slate-800/80 dark:hover:bg-slate-700 dark:text-slate-300 border border-slate-300/80 dark:border-white/10 flex items-center justify-center transition-all focus:outline-none focus:ring-2 focus:ring-blue-400" aria-label="Buka menu navigasi" aria-expanded="false">
                        <i class="fa-solid fa-bars text-sm" id="mobileMenuIconOpen"></i>
                        <i class="fa-solid fa-xmark text-sm hidden" id="mobileMenuIconClose"></i>
                    </button>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Drawer (R-10 Primary Glass Accent 1) -->
        @auth
        <div id="mobileMenuDrawer" class="hidden md:hidden border-t border-slate-200/80 dark:border-white/10 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl px-4 py-4 space-y-3 transition-all">
            <!-- User Profile in Mobile Menu -->
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-white/10">
                <div class="w-10 h-10 rounded-xl bg-blue-600/20 text-blue-700 dark:bg-blue-600/30 dark:text-blue-300 flex items-center justify-center font-bold text-base border border-blue-500/20 dark:border-blue-400/30">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 capitalize">Role: <span class="text-blue-600 dark:text-blue-400 font-medium">{{ auth()->user()->role }}</span></div>
                </div>
            </div>

            <!-- Mobile Navigation Links (each min-h-[44px] for thumb reachability) -->
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all min-h-[44px] {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 dark:bg-blue-600/25 dark:text-blue-300 font-semibold border border-blue-200 dark:border-blue-400/30' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-white' }}">
                    <i class="fa-solid fa-chart-line text-base text-blue-500 w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>

                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all min-h-[44px] {{ request()->routeIs('customers.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-600/25 dark:text-blue-300 font-semibold border border-blue-200 dark:border-blue-400/30' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-white' }}">
                    <i class="fa-solid fa-address-book text-base text-indigo-500 w-6 text-center"></i>
                    <span>Data Pelanggan</span>
                </a>

                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all min-h-[44px] {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700 dark:bg-blue-600/25 dark:text-blue-300 font-semibold border border-blue-200 dark:border-blue-400/30' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-white' }}">
                    <i class="fa-solid fa-users text-base text-purple-500 w-6 text-center"></i>
                    <span>Kelola User</span>
                </a>
                @endif
            </div>

            <!-- Logout Button in Mobile Menu -->
            <div class="pt-2 border-t border-slate-200 dark:border-white/10">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/40 border border-rose-200 dark:border-rose-500/30 transition-all min-h-[44px]">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Keluar dari Sistem</span>
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </nav>

    <!-- Main Content -->
    <main class="relative z-10 flex-grow w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-4 sm:py-8">
        @if(session('success'))
            <div class="mb-4 sm:mb-6 p-3.5 sm:p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-center justify-between shadow-sm dark:shadow-xl dark:shadow-emerald-950/30 backdrop-blur-sm">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <i class="fa-solid fa-circle-check text-lg sm:text-xl text-emerald-600 dark:text-emerald-400"></i>
                    <span class="text-xs sm:text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="w-9 h-9 flex items-center justify-center text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 focus:outline-none focus:ring-2 focus:ring-emerald-400 rounded-lg" aria-label="Tutup notifikasi"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 sm:mb-6 p-3.5 sm:p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 flex items-center justify-between shadow-sm dark:shadow-xl dark:shadow-rose-950/30 backdrop-blur-sm">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-lg sm:text-xl text-rose-600 dark:text-rose-400"></i>
                    <span class="text-xs sm:text-sm font-medium">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="w-9 h-9 flex items-center justify-center text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-400 rounded-lg" aria-label="Tutup notifikasi"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-slate-200 dark:border-white/5 bg-white/70 dark:bg-slate-950/80 py-4 sm:py-5 text-center text-xs text-slate-500 dark:text-slate-400 transition-colors px-4 sm:px-6 lg:px-8 xl:px-10">
        &copy; {{ date('Y') }} Central Ticket System. Mengelola 60 Billing Instances.
    </footer>

    <!-- Theme, Fullscreen & Mobile Menu Scripts -->
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
            const btn = document.getElementById('mobileMenuBtn');
            const iconOpen = document.getElementById('mobileMenuIconOpen');
            const iconClose = document.getElementById('mobileMenuIconClose');

            if (!drawer) return;

            const isClosed = drawer.classList.contains('hidden');
            if (isClosed) {
                drawer.classList.remove('hidden');
                if (btn) btn.setAttribute('aria-expanded', 'true');
                if (iconOpen) iconOpen.classList.add('hidden');
                if (iconClose) iconClose.classList.remove('hidden');
            } else {
                drawer.classList.add('hidden');
                if (btn) btn.setAttribute('aria-expanded', 'false');
                if (iconOpen) iconOpen.classList.remove('hidden');
                if (iconClose) iconClose.classList.add('hidden');
            }
        }

        // Close mobile drawer with Escape key (antislop R-32)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const drawer = document.getElementById('mobileMenuDrawer');
                if (drawer && !drawer.classList.contains('hidden')) {
                    toggleMobileMenu();
                }
            }
        });
    </script>
</body>
</html>
