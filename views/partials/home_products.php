<?php foreach($products as $p): ?>
<div class="group relative animate__animated animate__fadeInUp">
    <div class="glass rounded-[2rem] p-2 md:p-5 border border-white/60 relative overflow-hidden transition-all duration-300 hover:border-slate-200">
        
        <!-- Product Image (Clickable) -->
        <a hx-get="product.php?id=<?= $p['id'] ?>" hx-target="#page-content" hx-push-url="true" class="block">
            <div class="aspect-square rounded-[1.8rem] overflow-hidden bg-white mb-3 flex items-center justify-center p-2 relative group-hover:bg-slate-50 transition-colors duration-500" 
                 x-data="{ prodLoaded: false }" 
                 x-init="$nextTick(() => { if($el.querySelector('img').complete) prodLoaded = true; })">
                <div class="absolute inset-0 bg-slate-100 animate-pulse z-20" x-show="!prodLoaded" x-transition.opacity.duration.500ms></div>
                <img src="image.php?id=<?= $p['file_id'] ?>" 
                     @load="prodLoaded = true" 
                     class="w-full h-full object-contain group-hover:scale-110 transition duration-1000 ease-out relative z-10" loading="lazy" alt="<?= $p['name'] ?>">
            </div>
        </a>
        
        <div class="px-1 md:px-2 space-y-4 pb-2">
            <!-- Product Name (Clickable) -->
            <a hx-get="product.php?id=<?= $p['id'] ?>" hx-target="#page-content" hx-push-url="true">
                <h3 class="font-black text-sm md:text-xl tracking-tight text-slate-900 line-clamp-1 uppercase hover:text-slate-600 transition tracking-tighter leading-tight"><?= $p['name'] ?></h3>
            </a>
            <div class="flex items-center justify-between gap-2">
                <div class="flex flex-col">
                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest leading-none mb-1">Narx</span>
                    <span class="text-sm md:text-lg font-black text-black"><?= number_format($p['base_price'], 0, ',', ' ') ?> <span class="text-[10px]">so'm</span></span>
                </div>
                <!-- Real-time Add Button (Standalone or Modal) -->
                <?php if($p['variant_count'] > 0): ?>
                <button hx-get="api/get_variant_modal.php?id=<?= $p['id'] ?>" 
                        hx-target="body" 
                        hx-swap="beforeend"
                        class="w-10 h-10 md:w-14 md:h-14 rounded-2xl bg-black text-white flex items-center justify-center hover:scale-110 active:scale-90 transition-all cursor-pointer shadow-2xl shadow-black/20 group">
                    <svg class="w-5 h-5 md:w-7 md:h-7 group-active:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                </button>
                <?php else: ?>
                <button hx-post="api/add_to_cart.php" 
                        hx-vals='{"product_id": <?= $p['id'] ?>, "quantity": 1}'
                        hx-swap="none"
                        class="w-10 h-10 md:w-14 md:h-14 rounded-2xl bg-black text-white flex items-center justify-center hover:scale-110 active:scale-90 transition-all cursor-pointer shadow-2xl shadow-black/20 group">
                    <svg class="w-5 h-5 md:w-7 md:h-7 group-active:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Load More Button -->
<?php if($current_page < $total_pages): ?>
<div id="home-pagination" class="col-span-2 lg:col-span-4 flex justify-center pt-16 pb-12">
    <button hx-get="index.php?page=<?= $current_page + 1 ?>" 
            hx-target="#home-pagination" 
            hx-swap="outerHTML"
            class="group relative bg-white border border-slate-100 px-12 py-5 rounded-[2rem] font-black text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-all duration-500 shadow-xl shadow-slate-100 flex items-center gap-4 active:scale-95">
        <span>Yana yuklash</span>
        <svg class="w-4 h-4 group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
    </button>
</div>
<?php endif; ?>
