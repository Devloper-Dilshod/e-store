<div class="max-w-xl mx-auto bg-white p-6 md:p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-black tracking-tighter uppercase mb-2">Tasdiqlash</h1>
        <?php if($user): ?>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Xush kelibsiz, <?= htmlspecialchars($user['name']) ?>!</p>
        <?php else: ?>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest px-4 leading-relaxed">Buyurtma uchun ma'lumotlaringizni kiriting</p>
        <?php endif; ?>
    </div>
    
    <form action="api/place_order.php" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Ismingiz</label>
            <input type="text" name="full_name" required 
                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                   placeholder="Ismingizni kiriting"
                   class="w-full bg-slate-50 border-none rounded-[1.5rem] px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all font-medium">
        </div>

        <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">To'liq manzilingiz</label>
            <textarea name="address" required 
                      placeholder="Shahar, tuman, ko'cha, uy raqami..."
                      class="w-full bg-slate-50 border-none rounded-[1.5rem] px-6 py-4 outline-none focus:ring-2 focus:ring-black transition-all min-h-[120px] font-medium"></textarea>
        </div>

        <div class="space-y-2" x-data="{ 
            phone: '<?= htmlspecialchars($user['phone'] ?? '+998') ?>',
            formatPhone(val) {
                // Remove all non-numeric chars except the leading +
                let cleared = val.replace(/[^\d]/g, '');
                
                // Ensure it starts with 998
                if (!cleared.startsWith('998')) {
                    cleared = '998' + cleared;
                }
                
                // Limit to 12 digits (998 + 9 digits)
                cleared = cleared.substring(0, 12);
                
                // Format: +998 XX XXX XX XX
                let formatted = '+998';
                if (cleared.length > 3) {
                    formatted += ' ' + cleared.substring(3, 5);
                }
                if (cleared.length > 5) {
                    formatted += ' ' + cleared.substring(5, 8);
                }
                if (cleared.length > 8) {
                    formatted += ' ' + cleared.substring(8, 10);
                }
                if (cleared.length > 10) {
                    formatted += ' ' + cleared.substring(10, 12);
                }
                
                this.phone = formatted;
            }
        }">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-4">Telefon raqam</label>
            <input type="tel" name="phone" required 
                   x-model="phone"
                   @input="formatPhone($event.target.value)"
                   @keydown="if ($event.key === 'Backspace' && phone.length <= 4) $event.preventDefault()"
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
