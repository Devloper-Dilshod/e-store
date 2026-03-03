<div class="max-w-sm mx-auto mt-20 px-4 text-center animate__animated animate__fadeIn">
    <div class="glass p-12 rounded-[3rem] border border-white/60 shadow-2xl">
        <div class="text-6xl mb-6">🔐</div>
        <h1 class="text-2xl font-black tracking-tight uppercase mb-2">Kirish</h1>
        <p class="text-slate-400 text-sm font-medium leading-relaxed mb-8">
            Do'kondan foydalanish uchun Telegram botimiz orqali kiring
        </p>

        <?php if ($webapp_url): ?>
        <a href="<?= htmlspecialchars($webapp_url) ?>" 
           target="_blank"
           class="block bg-black text-white text-center py-4 px-8 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-black/20 hover:scale-105 active:scale-95 transition-all mb-4">
            🤖 Telegram orqali kirish
        </a>
        <?php else: ?>
        <div class="bg-slate-50 rounded-2xl p-6 text-sm text-slate-500 font-medium">
            Telegram botni sozlash uchun admin bilan bog'laning
        </div>
        <?php endif; ?>

        <p class="text-xs text-slate-300 font-medium mt-6">
            Xavfsiz kirish · Telegram ID asosida
        </p>
    </div>
</div>
