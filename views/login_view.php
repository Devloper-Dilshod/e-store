<div class="max-w-md mx-auto animate__animated animate__fadeInDown">
    <div class="glass p-8 md:p-12 rounded-[3rem] shadow-2xl border border-white relative overflow-hidden backdrop-blur-2xl">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-slate-100 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-slate-100 rounded-full blur-3xl opacity-50"></div>
        
        <div class="relative z-10 space-y-8">
            <div class="text-center space-y-2">
                <h1 class="text-4xl font-black tracking-tighter uppercase italic">Xush kelibsiz</h1>
                <p class="text-gray-400 font-bold text-xs uppercase tracking-[0.2em]">Hisobingizga kiring</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-2xl text-sm font-medium">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="space-y-4">
                    <div class="relative" x-data="{ 
                        phone: '+998 ',
                        formatPhone(val) {
                            let cleared = val.replace(/[^\d]/g, '');
                            if (!cleared.startsWith('998')) cleared = '998' + cleared;
                            cleared = cleared.substring(0, 12);
                            let formatted = '+998';
                            if (cleared.length > 3) formatted += ' ' + cleared.substring(3, 5);
                            if (cleared.length > 5) formatted += ' ' + cleared.substring(5, 8);
                            if (cleared.length > 8) formatted += ' ' + cleared.substring(8, 10);
                            if (cleared.length > 10) formatted += ' ' + cleared.substring(10, 12);
                            this.phone = formatted;
                        }
                    }">
                        <input type="tel" name="phone" required 
                               x-model="phone"
                               @input="formatPhone($event.target.value)"
                               @keydown="if ($event.key === 'Backspace' && phone.length <= 5) $event.preventDefault()"
                               placeholder="+998 XX XXX XX XX"
                               class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all font-medium">
                    </div>
                    
                    <div class="group relative" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" name="password" required 
                               placeholder="Parol"
                               class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all font-medium pr-14">
                        <button type="button" @click="show = !show" class="absolute right-5 top-4 text-gray-400 hover:text-black transition">
                            <svg x-show="!show" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="show" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.958 9.958 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-black text-white py-5 rounded-2xl font-black uppercase text-xs tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-black/20">Kirish</button>
            </form>

            <div class="text-center pt-4">
                <p class="text-gray-400 font-medium text-sm">
                    Hisobingiz yo'qmi? 
                    <a href="register.php" hx-get="register.php" hx-target="#page-content" hx-push-url="true" class="text-black font-black hover:underline underline-offset-4 ml-1">Ro'yxatdan o'ting</a>
                </p>
            </div>
        </div>
    </div>
</div>
