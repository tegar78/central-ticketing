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
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            orange: '#ea580c',
                            purple: '#2E286E',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }

        /* Neumorphism Palette & Shadows Matching User Reference Mockup */
        :root {
            --neu-surface: #edf2f8;
            --neu-text: #1e293b;
            --neu-card-shadow: 20px 20px 50px #cad4e2, -20px -20px 50px #ffffff;
            --neu-disc-shadow: 9px 9px 22px #cad4e2, -9px -9px 22px #ffffff;
            --neu-inset-shadow: inset 4px 4px 8px #cad4e2, inset -4px -4px 8px #ffffff;
            --neu-inset-focus: inset 4px 4px 8px #cad4e2, inset -4px -4px 8px #ffffff, 0 0 0 2px rgba(234, 88, 12, 0.35);
            --neu-tab-raised: 4px 4px 10px #cad4e2, -4px -4px 10px #ffffff;
            --neu-tab-inset: inset 3px 3px 6px #cad4e2, inset -3px -3px 6px #ffffff;
            --neu-btn-shadow: 6px 6px 18px rgba(234, 88, 12, 0.38), -4px -4px 12px #ffffff;
        }

        .dark {
            --neu-surface: #151821;
            --neu-text: #f1f5f9;
            --neu-card-shadow: 18px 18px 45px #0a0c10, -18px -18px 45px #202432;
            --neu-disc-shadow: 9px 9px 22px #0a0c10, -9px -9px 22px #202432;
            --neu-inset-shadow: inset 4px 4px 8px #0a0c10, inset -4px -4px 8px #202432;
            --neu-inset-focus: inset 4px 4px 8px #0a0c10, inset -4px -4px 8px #202432, 0 0 0 2px rgba(234, 88, 12, 0.45);
            --neu-tab-raised: 4px 4px 10px #0a0c10, -4px -4px 10px #202432;
            --neu-tab-inset: inset 3px 3px 6px #0a0c10, inset -3px -3px 6px #202432;
            --neu-btn-shadow: 6px 6px 18px rgba(234, 88, 12, 0.40), -4px -4px 12px #202432;
        }

        body {
            background-color: var(--neu-surface);
            color: var(--neu-text);
        }

        .neu-card {
            background-color: var(--neu-surface);
            box-shadow: var(--neu-card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .dark .neu-card {
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .neu-disc {
            background-color: var(--neu-surface);
            box-shadow: var(--neu-disc-shadow);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        .dark .neu-disc {
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .neu-inset-field {
            background-color: var(--neu-surface);
            box-shadow: var(--neu-inset-shadow);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .neu-inset-field:focus-within {
            box-shadow: var(--neu-inset-focus);
        }

        .neu-tab-active {
            background-color: var(--neu-surface);
            box-shadow: var(--neu-tab-inset);
            color: #ea580c;
        }

        .neu-tab-inactive {
            background-color: var(--neu-surface);
            box-shadow: var(--neu-tab-raised);
            color: #64748b;
        }
        .dark .neu-tab-inactive {
            color: #94a3b8;
        }

        .neu-checkbox {
            appearance: none;
            background-color: var(--neu-surface);
            box-shadow: var(--neu-inset-shadow);
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
        }
        .neu-checkbox:checked {
            background-color: #ea580c;
            box-shadow: 2px 2px 6px rgba(234, 88, 12, 0.4);
        }
        .neu-checkbox:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 11px;
            font-weight: bold;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .neu-submit-btn {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            box-shadow: var(--neu-btn-shadow);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .neu-submit-btn:hover {
            opacity: 0.96;
            transform: translateY(-1px);
        }
        .neu-submit-btn:active {
            box-shadow: inset 3px 3px 6px rgba(0, 0, 0, 0.35);
            transform: translateY(0);
        }

        .neu-theme-toggle {
            background-color: var(--neu-surface);
            box-shadow: 4px 4px 10px #cad4e2, -4px -4px 10px #ffffff;
            transition: all 0.2s ease;
        }
        .dark .neu-theme-toggle {
            box-shadow: 4px 4px 10px #0a0c10, -4px -4px 10px #202432;
        }
        .neu-theme-toggle:hover {
            transform: translateY(-1px);
        }
        .neu-theme-toggle:active {
            box-shadow: var(--neu-tab-inset);
            transform: translateY(0);
        }

        /* Autofill Transparent Neumorphic Fix */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px var(--neu-surface) inset !important;
            -webkit-text-fill-color: var(--neu-text) !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
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
