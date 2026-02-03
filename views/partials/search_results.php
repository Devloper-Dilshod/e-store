<!-- Results Count -->
<div class="mb-8 flex items-center justify-between">
    <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]"><?= $total_items ?> mahsulot topildi</p>
</div>

<!-- Products Grid -->
<?php if(empty($products)): ?>
    <div class="py-20 text-center glass rounded-[3rem] border border-white/50 shadow-inner">
        <div class="w-24 h-24 bg-slate-50 rounded-[2.5rem] flex items-center justify-center mx-auto mb-6 text-slate-300">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h3 class="text-2xl font-black uppercase tracking-tighter">Hech narsa topilmadi</h3>
        <p class="text-slate-400 font-bold">Boshqa so'z bilan qidirib ko'ring</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-10">
        <?php foreach($products as $p): ?>
        <div class="group relative animate__animated animate__fadeInUp">
            <div class="glass rounded-[2.5rem] p-3 md:p-5 border border-white/60 relative overflow-hidden transition-all duration-300 hover:border-slate-200">
                
                <!-- Product Image (Clickable) -->
                <a hx-get="product.php?id=<?= $p['id'] ?>" hx-target="#page-content" hx-push-url="true" class="block">
                    <div class="aspect-[4/5] md:aspect-square rounded-[2rem] overflow-hidden bg-white mb-4 flex items-center justify-center p-3 relative group-hover:bg-slate-50 transition-colors duration-500" 
                         x-data="{ loaded: false }"
                         x-init="$nextTick(() => { if($el.querySelector('img').complete) loaded = true; })">
                        <div class="absolute inset-0 skeleton z-20" x-show="!loaded" x-transition.opacity.duration.500ms></div>
                        <img src="image.php?id=<?= $p['file_id'] ?>" 
                             @load="loaded = true"
                             class="w-full h-full object-contain group-hover:scale-110 transition duration-1000 ease-out relative z-10" 
                             x-show="loaded"
                             loading="lazy" alt="<?= $p['name'] ?>">
                    </div>
                </a>
                
                <div class="px-1 md:px-2 space-y-4 pb-2">
                    <!-- Product Name (Clickable) -->
                    <a hx-get="product.php?id=<?= $p['id'] ?>" hx-target="#page-content" hx-push-url="true">
                        <h3 class="font-black text-sm md:text-xl tracking-tight text-slate-900 line-clamp-1 uppercase hover:text-slate-600 transition leading-tight"><?= $p['name'] ?></h3>
                    </a>
                    
                    <div class="flex items-center justify-between gap-1">
                        <div class="flex flex-col">
                            <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">Narx</span>
                            <span class="text-sm md:text-lg font-black text-black"><?= number_format($p['base_price']) ?> <span class="text-[10px]">so'm</span></span>
                        </div>
                        <!-- Real-time Add Button (Standalone) -->
                        <button hx-post="api/add_to_cart.php" 
                                hx-vals='{"product_id": <?= $p['id'] ?>, "quantity": 1}'
                                hx-swap="none"
                                class="w-10 h-10 md:w-14 md:h-14 rounded-2xl bg-black text-white flex items-center justify-center hover:scale-110 active:scale-90 transition-all cursor-pointer shadow-2xl shadow-black/20 group">
                            <svg class="w-5 h-5 md:w-7 md:h-7 group-active:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Load More Button -->
    <?php if($current_page < $total_pages): ?>
    <div id="pagination-container" class="flex justify-center pt-16 pb-4">
        <button hx-get="search.php?q=<?= urlencode($query) ?>&cat=<?= $cat_id ?>&page=<?= $current_page + 1 ?>" 
                hx-target="#pagination-container" 
                hx-swap="outerHTML"
                class="group relative bg-white border border-slate-200 px-12 py-5 rounded-[2rem] font-black text-[10px] uppercase tracking-widest hover:bg-slate-900 hover:text-white transition-all duration-500 shadow-xl shadow-slate-100 flex items-center gap-4 active:scale-95">
            <span>Yana yuklash</span>
            <svg class="w-4 h-4 group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
        </button>
    </div>
    <?php endif; ?>
<?php endif; ?>
