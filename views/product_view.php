<?php if(!$product): ?>
    <div class='p-20 text-center glass rounded-[3rem] border border-white/50 shadow-inner'>
        <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-50 rounded-full mb-6">
            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h2 class="text-2xl font-black uppercase tracking-tighter">Mahsulot topilmadi</h2>
        <a href="index.php" hx-get="index.php" hx-target="#page-content" hx-push-url="true" class="text-slate-400 font-bold hover:text-black mt-4 inline-block underline underline-offset-4">Bosh sahifaga qaytish</a>
    </div>
<?php else: ?>
<div class="max-w-7xl mx-auto" x-data="{ 
    selectedVariant: <?= count($variants) > 0 ? $variants[0]['id'] : 'null' ?>,
    basePrice: <?= $product['base_price'] ?>,
    currentPrice: <?= count($variants) > 0 ? $variants[0]['price'] : $product['base_price'] ?>,
    currentImage: 'image.php?id=<?= (count($variants) > 0 && $variants[0]['file_id']) ? $variants[0]['file_id'] : $product['file_id'] ?>',
    showViewer: false,
    loaded: false,
    updateVariant(id, price, imgId) {
        this.selectedVariant = id;
        this.currentPrice = price;
        if(imgId) {
            this.loaded = false;
            this.currentImage = 'image.php?id=' + imgId;
        }
    }
}">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start">
        
        <!-- Image Section -->
        <div class="space-y-4">
            <div @click="showViewer = true" class="aspect-square glass rounded-[2.5rem] overflow-hidden shadow-2xl border border-white/60 flex items-center justify-center p-4 relative cursor-zoom-in group"
                 x-init="$nextTick(() => { if($el.querySelector('img').complete) loaded = true; })">
                <div class="absolute inset-0 skeleton z-20" x-show="!loaded" x-transition.opacity.duration.500ms></div>
                <img :src="currentImage" 
                     @load="loaded = true"
                     x-show="loaded"
                     class="w-full h-full object-contain rounded-3xl transition-all duration-1000 group-hover:scale-110 ease-out relative z-10" alt="<?= $product['name'] ?>">
                
                <div class="absolute bottom-6 right-6 bg-black/50 backdrop-blur-md text-white p-3 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity z-30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                </div>
            </div>

            <!-- Thumbnail Gallery -->
            <div class="flex gap-4 overflow-x-auto py-2 no-scrollbar">
                <?php foreach($gallery as $img): if(!$img) continue; ?>
                <button @click="currentImage='image.php?id=<?= $img ?>'; loaded=false" 
                        class="w-20 h-20 rounded-2xl glass border-2 transition-all p-1.5 shrink-0"
                        :class="currentImage.includes('<?= $img ?>') ? 'border-black' : 'border-transparent opacity-60 hover:opacity-100'">
                    <img src="image.php?id=<?= $img ?>" class="w-full h-full object-contain rounded-xl">
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Info Section -->
        <div class="space-y-8 lg:sticky lg:top-10">
            <div class="space-y-4">
                <nav class="flex text-[10px] font-black uppercase tracking-widest text-slate-400 gap-2 mb-2">
                    <a hx-get="index.php" hx-target="#page-content" hx-push-url="true" class="hover:text-black transition">Bosh sahifa</a>
                    <span>/</span>
                    <span class="text-black"><?= $product['name'] ?></span>
                </nav>
                <h1 class="text-4xl md:text-5xl font-black tracking-tighter text-slate-900 uppercase leading-tight"><?= $product['name'] ?></h1>
            </div>

            <div class="flex items-center gap-4">
                <div class="bg-black text-white px-8 py-4 rounded-[1.8rem] shadow-xl shadow-black/20">
                    <span class="text-2xl font-black tracking-tight" x-text="new Intl.NumberFormat().format(currentPrice) + ' so\'m'"></span>
                </div>
                <?php if($product['has_discount']): ?>
                <div class="bg-red-50 text-red-600 px-5 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest animate-pulse">
                    -<?= $product['discount_percent'] ?>% AKSIYA
                </div>
                <?php endif; ?>
            </div>

            <div class="bg-white/50 rounded-[2rem] p-6 border border-white/50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Tavsif:</h4>
                <p class="text-sm md:text-base text-slate-600 leading-relaxed font-bold">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </p>
            </div>

            <?php if(count($variants) > 0): ?>
            <div class="space-y-4">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-2">Variantni tanlang:</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach($variants as $idx => $v): ?>
                    <button 
                        @click="updateVariant(<?= $v['id'] ?>, <?= $v['price'] ?>, '<?= $v['file_id'] ?>')"
                        :class="selectedVariant == <?= $v['id'] ?> ? 'bg-black text-white shadow-xl shadow-black/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-100'"
                        class="px-6 py-4 rounded-2xl font-black transition-all duration-300 text-xs uppercase tracking-tight active:scale-95">
                        <?= ($idx === 0 && count($variants) > 1 && empty($v['variant_name'])) ? 'Oddiy' : htmlspecialchars($v['variant_name']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row gap-4 pt-4 pb-12">
                <form hx-post="api/add_to_cart.php" hx-swap="none" class="flex-1">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="variant_id" :value="selectedVariant">
                    <button type="submit" 
                            @click="showSuccess = true; setTimeout(() => showSuccess = false, 2000)"
                            x-data="{ showSuccess: false }"
                            class="w-full bg-black text-white py-6 rounded-[2rem] font-black uppercase text-xs tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-2xl shadow-black/30 flex items-center justify-center gap-3 relative overflow-hidden group">
                        <span x-show="!showSuccess" class="flex items-center gap-3 relative z-10 transition-transform group-active:translate-y-1">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            Savatchaga qo'shish
                        </span>
                        <span x-show="showSuccess" x-cloak class="flex items-center gap-3 text-green-400 relative z-10 animate__animated animate__bounceIn">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            Qo'shildi!
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div x-show="showViewer" x-transition.opacity @keydown.escape.window="showViewer = false" class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center p-4 md:p-10" x-cloak>
        <button @click="showViewer = false" class="absolute top-6 right-6 text-white/50 hover:text-white transition p-2">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <img :src="currentImage" class="max-w-full max-h-full object-contain rounded-[2rem] animate__animated animate__zoomIn animate__faster">
    </div>
</div>
<?php endif; ?>
