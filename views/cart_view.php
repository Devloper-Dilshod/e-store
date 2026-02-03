<div class="max-w-5xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-black mb-8 tracking-tighter uppercase">Savatcha</h1>

    <?php if(empty($cart_items)): ?>
        <div class="text-center py-20 glass rounded-[3rem] shadow-xl border border-white/50">
            <div class="w-24 h-24 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                 <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
            <p class="text-slate-400 mb-8 text-xl font-black uppercase tracking-widest">Savatchangiz bo'sh</p>
            <a href="index.php" hx-get="index.php" hx-target="#page-content" hx-push-url="true" class="inline-block bg-black text-white px-8 py-4 rounded-2xl font-black uppercase text-sm tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-black/20">Xaridni boshlash</a>
        </div>
    <?php else: ?>

        <div class="flex flex-col lg:flex-row gap-6 md:gap-8 items-start" id="cart-container">
            <!-- Cart Items -->
            <div class="flex-1 space-y-4 w-full" id="cart-items-list">
                <?php foreach($cart_items as $item): ?>
                    <div class="glass p-4 md:p-5 rounded-3xl border border-white/50 flex gap-4 items-center hover:border-slate-200 transition">
                        <div class="w-20 h-20 md:w-24 md:h-24 bg-white rounded-2xl overflow-hidden shrink-0 border border-slate-100 p-2">
                            <?php if($item['image_id']): ?>
                                <img src="image.php?id=<?= $item['image_id'] ?>" class="w-full h-full object-contain">
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-black text-sm md:text-lg uppercase tracking-tight line-clamp-1"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="text-slate-400 text-xs md:text-sm font-bold"><?= number_format($item['price'], 0, ',', ' ') ?> so'm</p>
                        </div>
                        <div class="flex flex-col items-end gap-2 shrink-0">
                             <div class="flex items-center bg-slate-100 rounded-xl">
                                <button hx-post="api/update_cart.php" 
                                        hx-vals='{"key": "<?= $item['key'] ?>", "action": "decrease"}'
                                        hx-target="#cart-container"
                                        hx-swap="outerHTML"
                                        class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center text-slate-500 hover:text-black hover:bg-white rounded-l-xl transition font-black text-lg">-</button>
                                <span class="w-8 md:w-10 text-center text-sm font-black"><?= $item['quantity'] ?></span>
                                <button hx-post="api/update_cart.php" 
                                        hx-vals='{"key": "<?= $item['key'] ?>", "action": "increase"}'
                                        hx-target="#cart-container"
                                        hx-swap="outerHTML"
                                        class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center text-slate-500 hover:text-black hover:bg-white rounded-r-xl transition font-black text-lg">+</button>
                             </div>
                             <span class="font-black text-base md:text-lg"><?= number_format($item['total'], 0, ',', ' ') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="w-full lg:w-96 glass p-6 md:p-8 rounded-[2.5rem] shadow-xl border border-white/50 lg:sticky lg:top-24" id="cart-summary">
                <h3 class="font-black text-xl md:text-2xl mb-6 uppercase tracking-tight">Buyurtma</h3>
                <div class="space-y-4 mb-8">
                    <div class="flex justify-between text-slate-500 text-sm">
                        <span class="font-bold">Mahsulotlar</span>
                        <span class="font-black text-black"><?= count($cart_items) ?> ta</span>
                    </div>
                    <div class="flex justify-between text-slate-500 text-sm">
                        <span class="font-bold">Yetkazib berish</span>
                        <span class="font-black text-green-600">Bepul</span>
                    </div>
                    <div class="h-px bg-slate-200"></div>
                    <div class="flex justify-between text-xl md:text-2xl font-black">
                        <span>Jami</span>
                        <span><?= number_format($total_sum, 0, ',', ' ') ?></span>
                    </div>
                </div>
                
                <a href="checkout.php" hx-get="checkout.php" hx-target="#page-content" hx-push-url="true" class="block w-full bg-black text-white text-center py-4 rounded-2xl font-black uppercase text-sm tracking-widest hover:scale-105 active:scale-95 transition-all shadow-xl shadow-black/20">Buyurtma berish</a>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <p class="mt-4 text-[10px] text-slate-400 font-bold text-center uppercase tracking-widest">Mehmon bo'lib buyurtma berish mumkin</p>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>
