<div class="max-w-7xl mx-auto">
    <!-- Search & Filter Header -->
    <div class="mb-10 space-y-6">
        <div class="relative group">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" 
                   placeholder="Mahsulot qidirish..." 
                   hx-trigger="keyup changed delay:500ms"
                   hx-get="search.php"
                   hx-target="#search-results"
                   hx-include="[name='cat']"
                   hx-indicator=".search-indicator"
                   class="w-full bg-white border border-gray-100 rounded-3xl px-12 py-5 shadow-sm focus:ring-2 focus:ring-black outline-none transition-all text-lg font-medium">
            <svg class="w-6 h-6 absolute left-4 top-5 text-gray-400 group-focus-within:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <div class="search-indicator htmx-indicator absolute right-6 top-6">
                <div class="animate-spin h-5 w-5 border-2 border-black border-t-transparent rounded-full"></div>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <!-- Categories Scroll -->
            <div class="overflow-x-auto no-scrollbar -mx-4 px-4 pb-2">
                <div class="flex gap-3 min-w-max">
                    <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" 
                       class="px-8 py-4 rounded-[1.8rem] font-black text-[10px] uppercase tracking-widest transition-all duration-300 active:scale-90 <?= !$cat_id ? 'bg-black text-white shadow-2xl shadow-black/40' : 'bg-white border border-slate-100 text-slate-400 hover:border-black hover:text-black' ?>">
                        Barchasi
                    </a>
                    <?php foreach($categories as $c): ?>
                    <a hx-get="search.php?cat=<?= $c['id'] ?>" hx-target="#page-content" hx-push-url="true" 
                       class="px-8 py-4 rounded-[1.8rem] font-black text-[10px] uppercase tracking-widest transition-all duration-300 active:scale-90 <?= $cat_id == $c['id'] ? 'bg-black text-white shadow-2xl shadow-black/40' : 'bg-white border border-slate-100 text-slate-400 hover:border-black hover:text-black' ?>">
                        <?= $c['name'] ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest hidden md:block">Natijalar</p>
                <div class="flex items-center gap-3 bg-white border border-slate-100 rounded-2xl p-1.5 shadow-sm ml-auto">
                    <span class="pl-3 text-[9px] font-black uppercase text-slate-400 tracking-widest">Saralash</span>
                    <select name="sort" 
                            hx-get="search.php" 
                            hx-target="#search-results" 
                            hx-include="[name='q'], [name='cat']"
                            hx-push-url="true"
                            class="bg-slate-50 border-none rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-tight focus:ring-0 outline-none cursor-pointer hover:bg-slate-100 transition-colors">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>✨ Yangi</option>
                        <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>💸 Arzon</option>
                        <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>💎 Qimmat</option>
                        <option value="alpha" <?= $sort == 'alpha' ? 'selected' : '' ?>>🔠 Alifbo</option>
                        <option value="discount" <?= $sort == 'discount' ? 'selected' : '' ?>>🔥 Chegirma</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div id="search-results">
        <?php require_once 'views/partials/search_results.php'; ?>
    </div>
</div>
