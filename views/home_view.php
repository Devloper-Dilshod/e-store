<!-- Smaller Horizontal Categories -->
<div class="max-w-7xl mx-auto px-4 mb-10 mt-6">
    <div class="overflow-x-auto no-scrollbar flex gap-4 py-2">
        <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" class="shrink-0 flex items-center gap-3 bg-black text-white px-8 py-4 rounded-2xl shadow-xl cursor-pointer hover:scale-105 transition-all active:scale-95">
            <span class="font-black text-sm uppercase">Barchasi</span>
        </a>
        <?php foreach($categories as $c): ?>
        <a hx-get="search.php?cat=<?= $c['id'] ?>" hx-target="#page-content" hx-push-url="true" class="shrink-0 flex items-center gap-4 bg-white border border-slate-100 text-slate-900 px-6 py-4 rounded-2xl shadow-sm cursor-pointer hover:border-black hover:shadow-xl transition-all active:scale-95">
            <?php if($c['file_id']): ?>
                <img src="image.php?id=<?= $c['file_id'] ?>" class="w-8 h-8 object-cover rounded-xl" alt="<?= $c['name'] ?>">
            <?php endif; ?>
            <span class="font-black text-sm uppercase"><?= $c['name'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if(!empty($posters)): ?>
<!-- Premium Posters Section (Below Categories) with Auto-slide -->
<div class="px-4 mb-20 mt-6" 
     x-data="{ 
        active: 0, 
        count: <?= count($posters) ?>,
        init() {
            if (this.count > 1) {
                setInterval(() => {
                    this.active = (this.active + 1) % this.count;
                    const el = this.$refs.slider;
                    const card = el.querySelector('.poster-card');
                    if (card && el) {
                        const width = card.offsetWidth + 32; // card width + gap
                        el.scrollTo({ left: this.active * width, behavior: 'smooth' });
                    }
                }, 10000);
            }
        }
     }">
    <div x-ref="slider" class="max-w-7xl mx-auto overflow-x-auto no-scrollbar snap-x snap-mandatory flex gap-8 pb-8 scroll-smooth">
        <?php foreach($posters as $p): 
            $link = $p['link'] ?? 'search.php';
            $isExternal = strpos($link, 'http') === 0;
        ?>
        <a <?= $isExternal ? 'href="'.$link.'" target="_blank"' : 'hx-get="'.$link.'" hx-target="#page-content" hx-push-url="true"' ?>
           href="<?= htmlspecialchars($link) ?>"
           x-data="{ imgLoaded: false }"
           class="poster-card snap-center shrink-0 w-[95%] md:w-[900px] aspect-[3/2] md:aspect-[21/9] bg-white rounded-[3rem] overflow-hidden shadow-2xl relative group border border-white/50 block animate__animated animate__fadeIn cursor-pointer transition-transform active:scale-[0.98]">
            <div class="absolute inset-0 bg-slate-100 animate-pulse" x-show="!imgLoaded"></div>
            <img src="image.php?id=<?= $p['file_id'] ?>" 
                 @load="imgLoaded = true" 
                 class="w-full h-full object-cover transition duration-1000 group-hover:scale-105" alt="Banner">
            
            <!-- Subtle Overlay Hint -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-500"></div>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Dots Indicator -->
    <?php if(count($posters) > 1): ?>
    <div class="flex justify-center gap-2 -mt-4">
        <?php foreach($posters as $index => $p): ?>
        <div class="w-2 h-2 rounded-full transition-all duration-500"
             :class="active == <?= $index ?> ? 'w-8 bg-black' : 'bg-slate-200'"></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Products Grid (Optimized with Pagination) -->
<div class="max-w-7xl mx-auto px-4 pb-20">
    <div class="flex items-center justify-between mb-10">
        <h2 class="text-4xl font-black tracking-tighter uppercase">Siz uchun</h2>
        <div class="h-[3px] flex-1 bg-slate-100 mx-8 hidden md:block rounded-full"></div>
    </div>

    <div id="home-products-grid" class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-10">
        <?php include 'views/partials/home_products.php'; ?>
    </div>
</div>
