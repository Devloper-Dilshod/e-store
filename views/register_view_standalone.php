<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ro'yxatdan o'tish - E-STORE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="glass p-8 md:p-12 rounded-3xl shadow-2xl border border-white">
            <div class="text-center space-y-2 mb-8">
                <h1 class="text-4xl font-black tracking-tighter uppercase">Ro'yxatdan o'tish</h1>
                <p class="text-gray-400 font-bold text-xs uppercase tracking-widest">Yangi hisob yarating</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-2xl text-sm font-medium mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="space-y-5">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold mb-2 text-gray-700">To'liq ismingiz</label>
                        <input type="text" name="name" required 
                               placeholder="Ismingizni kiriting"
                               class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all font-medium">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold mb-2 text-gray-700">Telefon raqam</label>
                        <div class="relative">
                            <span class="absolute left-6 top-4 text-gray-900 font-medium pointer-events-none select-none">+998</span>
                            <input type="tel" name="phone" required 
                                   placeholder="XX XXX XX XX"
                                   maxlength="9"
                                   pattern="[0-9]{9}"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                                   class="w-full bg-gray-50 border-none rounded-2xl pl-20 pr-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all font-medium">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Faqat 9 ta raqam kiriting</p>
                    </div>
                    
                    <div x-data="{ show: false }">
                        <label class="block text-sm font-bold mb-2 text-gray-700">Parol</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" name="password" required 
                                   placeholder="Parol yarating"
                                   class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 pr-14 outline-none focus:ring-2 focus:ring-black transition-all font-medium">
                            <button type="button" @click="show = !show" class="absolute right-5 top-4 text-gray-400 hover:text-black transition">
                                <svg x-show="!show" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.958 9.958 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-black text-white py-5 rounded-2xl font-black uppercase text-xs tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-black/20">
                    Ro'yxatdan o'tish
                </button>
            </form>

            <div class="text-center pt-6">
                <p class="text-gray-400 font-medium text-sm">
                    Hisobingiz bormi? 
                    <a href="login.php" class="text-black font-black hover:underline underline-offset-4 ml-1">Kirish</a>
                </p>
            </div>
        </div>
        
        <div class="text-center mt-6">
            <a href="index.php" class="text-gray-600 hover:text-black font-medium text-sm">← Bosh sahifaga qaytish</a>
        </div>
    </div>
</body>
</html>
