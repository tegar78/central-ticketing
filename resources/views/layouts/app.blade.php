<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Central Ticket System') }}</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
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
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-slate-800/80 backdrop-blur border-b border-slate-700/60 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                            <i class="fa-solid fa-headset text-lg"></i>
                        </div>
                        <div>
                            <span class="font-bold text-lg text-white tracking-tight">Central Ticket</span>
                            <span class="text-xs block text-blue-400 font-medium">60 Billing Portal</span>
                        </div>
                    </a>

                    @auth
                    <div class="hidden md:flex items-center gap-2 ml-4">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-chart-line mr-1.5"></i> Dashboard
                        </a>
                        @if(in_array(auth()->user()->role, ['admin', 'operator']))
                        <a href="{{ route('customers.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('customers.*') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-address-book mr-1.5"></i> Data Pelanggan
                        </a>
                        <a href="{{ route('users.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('users.*') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-users mr-1.5"></i> Kelola User
                        </a>
                        @endif
                    </div>
                    @endauth
                </div>

                @auth
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700">
                        <div class="w-8 h-8 rounded-full bg-blue-600/30 text-blue-400 flex items-center justify-center font-bold text-sm border border-blue-500/30">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="text-left hidden sm:block">
                            <div class="text-sm font-semibold text-slate-200 leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-slate-400 capitalize">
                                Role: <span class="text-blue-400 font-medium">{{ auth()->user()->role }}</span>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" title="Logout" class="p-2.5 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-lg transition-all border border-transparent hover:border-slate-700">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-xl"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-200"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-950/60 py-4 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Central Ticket System - Managing 60 Billing Instances
    </footer>
</body>
</html>
