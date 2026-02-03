<div class="max-w-xl mx-auto bg-white p-6 md:p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
    <h1 class="text-3xl font-black mb-6 text-center tracking-tighter uppercase">Tasdiqlash</h1>
    
    <form action="api/place_order.php" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Ismingiz</label>
            <input type="text" name="full_name" required 
                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                   placeholder="Ismingizni kiriting"
                   class="w-full bg-slate-50 border-none rounded-[1.5rem] px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all font-medium">
        </div>

        <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">To'liq manzilingiz</label>
            <textarea name="address" required 
                      placeholder="Shahar, tuman, ko'cha, uy raqami..."
                      class="w-full bg-slate-50 border-none rounded-[1.5rem] px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all min-h-[120px] font-medium"></textarea>
        </div>

        <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Telefon raqam</label>
            <input type="tel" name="phone" required 
                   placeholder="+998 90 123 45 67"
                   class="w-full bg-slate-50 border-none rounded-[1.5rem] px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all font-medium">
        </div>

        <div class="bg-slate-900 text-white p-6 rounded-[2rem] space-y-3">
             <div class="flex justify-between text-slate-400 text-xs font-bold uppercase tracking-wider items-center">
                <span>Jami to'lov:</span>
                <span class="text-white text-lg md:text-xl font-black"><?= number_format($total_sum, 0, ',', ' ') ?> so'm</span>
             </div>
             <p class="text-[9px] text-slate-500 font-bold leading-relaxed uppercase">Buyurtma berish tugmasini bossangiz, operatorlarimiz siz bilan bog'lanishadi.</p>
        </div>

        <div class="flex gap-4">
            <a hx-get="cart.php" hx-target="#page-content" hx-push-url="true" class="flex-1 text-center py-4 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-400 hover:text-black transition cursor-pointer">Bekor qilish</a>
            <button type="submit" class="flex-[2] bg-black text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-black/20">Buyurtma berish</button>
        </div>
    </form>
</div>
