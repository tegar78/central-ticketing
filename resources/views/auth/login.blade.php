<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MANAGEMENT TICKET</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
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
    <!-- Tailwind Configuration -->
    <script src="{{ asset('js/tailwind-config.js') }}"></script>
    <!-- Authentication Stylesheet (Neumorphism Design System) -->
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative overflow-hidden transition-colors duration-200">

    <!-- Theme Toggle Floating Button -->
    <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-20">
        <button type="button" onclick="toggleTheme()" class="neu-theme-toggle w-10 h-10 rounded-2xl text-slate-600 dark:text-amber-300 flex items-center justify-center focus:outline-none" title="Ganti Mode Terang atau Gelap" aria-label="Ganti mode tampilan">
            <i class="fa-solid fa-moon text-sm block dark:hidden"></i>
            <i class="fa-solid fa-sun text-sm hidden dark:block"></i>
        </button>
    </div>

    <!-- Main Card Container -->
    <div class="w-full max-w-[420px] relative z-10 py-4">
        <div class="neu-card rounded-[38px] p-7 sm:p-9 transition-colors">

            <!-- Logo Raised Disc (Aligned Center) -->
            <div class="flex justify-center mb-5">
                <div class="neu-disc w-28 h-28 sm:w-32 sm:h-32 rounded-full flex items-center justify-center p-4 transition-transform duration-300 hover:scale-105 select-none">
                    <img src="{{ asset('images/logo.png') }}" alt="Gayuhnet Logo" class="w-20 h-20 sm:w-24 sm:h-24 object-contain">
                </div>
            </div>

            <!-- Welcome Title & Subtitle -->
            <div class="text-center mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Selamat Datang</h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Masuk ke akun Anda untuk melanjutkan</p>
            </div>

            <!-- Tab Switcher (Email / Phone) -->
            <div class="grid grid-cols-2 gap-3.5 mb-6">
                <button type="button" id="tabEmail" onclick="switchLoginMode('email')" 
                        class="neu-tab-active py-2.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm font-bold transition-all duration-200">
                    <i class="fa-solid fa-envelope text-xs sm:text-sm text-orange-500" id="tabEmailIcon"></i>
                    <span>Email</span>
                </button>
                <button type="button" id="tabPhone" onclick="switchLoginMode('phone')" 
                        class="neu-tab-inactive py-2.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm font-semibold transition-all duration-200">
                    <i class="fa-solid fa-phone text-xs sm:text-sm text-slate-400 dark:text-slate-500" id="tabPhoneIcon"></i>
                    <span>Phone</span>
                </button>
            </div>

            @if($errors->any())
                <div class="mb-5 p-3 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-400 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-sm text-rose-500 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-4 sm:space-y-5">
                @csrf

                <!-- Input: Email atau Telepon -->
                <div>
                    <label id="loginInputLabel" class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 dark:text-slate-400 mb-2">
                        EMAIL ATAU TELEPON
                    </label>
                    <div class="neu-inset-field rounded-2xl flex items-center px-4 py-1.5 relative">
                        <i class="fa-solid fa-user text-slate-400 text-xs sm:text-sm mr-3 shrink-0" id="loginFieldIcon"></i>
                        <input type="text" name="email" id="loginInputField" value="{{ old('email') }}" required autofocus
                               class="w-full py-2.5 bg-transparent border-none focus:outline-none text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 text-xs sm:text-sm font-medium"
                               placeholder="Masukkan email atau nomor telepon">
                    </div>
                </div>

                <!-- Input: Password with Show/Hide Eye Icon -->
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 dark:text-slate-400 mb-2">
                        PASSWORD
                    </label>
                    <div class="neu-inset-field rounded-2xl flex items-center px-4 py-1.5 relative">
                        <i class="fa-solid fa-lock text-slate-400 text-xs sm:text-sm mr-3 shrink-0"></i>
                        <input type="password" name="password" id="passwordInput" required
                               class="w-full py-2.5 pr-8 bg-transparent border-none focus:outline-none text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 text-xs sm:text-sm font-medium"
                               placeholder="Masukkan password">
                        <button type="button" onclick="togglePasswordVisibility()" id="togglePasswordBtn"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none"
                                title="Tampilkan password" aria-label="Tampilkan atau sembunyikan password">
                            <i class="fa-regular fa-eye text-xs sm:text-sm" id="passwordEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center gap-2.5 select-none pt-1">
                    <input type="checkbox" name="remember" id="rememberMe" class="neu-checkbox w-4 h-4 sm:w-4 sm:h-4">
                    <label for="rememberMe" class="text-xs text-slate-600 dark:text-slate-400 font-medium cursor-pointer">
                        Ingat Saya
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="neu-submit-btn w-full min-h-[48px] py-3 px-5 rounded-2xl text-white font-bold text-sm flex items-center justify-center gap-2 focus:outline-none mt-2">
                    <span>Masuk</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Scripts: Theme, Password Toggle & Tab Switcher -->
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

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('passwordEyeIcon');
            const toggleBtn = document.getElementById('togglePasswordBtn');

            if (!passwordInput || !eyeIcon) return;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                toggleBtn.setAttribute('title', 'Sembunyikan password');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                toggleBtn.setAttribute('title', 'Tampilkan password');
            }
        }

        function switchLoginMode(mode) {
            const tabEmail = document.getElementById('tabEmail');
            const tabPhone = document.getElementById('tabPhone');
            const tabEmailIcon = document.getElementById('tabEmailIcon');
            const tabPhoneIcon = document.getElementById('tabPhoneIcon');
            const inputLabel = document.getElementById('loginInputLabel');
            const inputField = document.getElementById('loginInputField');
            const fieldIcon = document.getElementById('loginFieldIcon');

            if (mode === 'phone') {
                tabPhone.className = 'neu-tab-active py-2.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm font-bold transition-all duration-200';
                tabEmail.className = 'neu-tab-inactive py-2.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm font-semibold transition-all duration-200';

                tabPhoneIcon.className = 'fa-solid fa-phone text-xs sm:text-sm text-orange-500';
                tabEmailIcon.className = 'fa-solid fa-envelope text-xs sm:text-sm text-slate-400 dark:text-slate-500';

                inputLabel.textContent = 'NOMOR TELEPON';
                inputField.placeholder = 'Contoh: 081234567890';
                inputField.type = 'tel';
                fieldIcon.className = 'fa-solid fa-phone text-slate-400 text-xs sm:text-sm mr-3 shrink-0';
            } else {
                tabEmail.className = 'neu-tab-active py-2.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm font-bold transition-all duration-200';
                tabPhone.className = 'neu-tab-inactive py-2.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-xs sm:text-sm font-semibold transition-all duration-200';

                tabEmailIcon.className = 'fa-solid fa-envelope text-xs sm:text-sm text-orange-500';
                tabPhoneIcon.className = 'fa-solid fa-phone text-xs sm:text-sm text-slate-400 dark:text-slate-500';

                inputLabel.textContent = 'EMAIL ATAU TELEPON';
                inputField.placeholder = 'Masukkan email atau nomor telepon';
                inputField.type = 'text';
                fieldIcon.className = 'fa-solid fa-user text-slate-400 text-xs sm:text-sm mr-3 shrink-0';
            }
            inputField.focus();
        }
    </script>
</body>
</html>
