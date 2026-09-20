<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Central Ticket System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 mx-auto flex items-center justify-center text-white text-2xl shadow-xl shadow-blue-500/30 mb-4">
                <i class="fa-solid fa-headset"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Central Ticket System</h1>
            <p class="text-sm text-slate-400 mt-1">Multi-Tenant Management untuk 60 Billing App</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl backdrop-blur">
            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full pl-10 pr-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all text-sm"
                            placeholder="admin@central.local">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full pl-10 pr-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/30 transition-all duration-200 flex items-center justify-center gap-2">
                    <span>Masuk Portal Tiket</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Sample Credentials Helper -->
            <div class="mt-8 pt-6 border-t border-slate-800 text-xs text-slate-400">
                <div class="font-semibold text-slate-300 mb-2">Akun Testing Terdaftar:</div>
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <div class="bg-slate-800/60 p-2 rounded border border-slate-800">
                        <span class="block font-bold text-blue-400">Admin/Operator</span>
                        <span>admin@central.local</span>
                    </div>
                    <div class="bg-slate-800/60 p-2 rounded border border-slate-800">
                        <span class="block font-bold text-emerald-400">Teknisi A</span>
                        <span>teknisi1@central.local</span>
                    </div>
                </div>
                <div class="text-[10px] text-slate-500 mt-2 text-center">Password semua akun: <code class="text-slate-300">password</code></div>
            </div>
        </div>
    </div>
</body>
</html>
