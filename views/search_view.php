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

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex flex-wrap gap-2">
                <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" class="px-5 py-3 rounded-2xl font-bold text-sm transition-all <?= !$cat_id ? 'bg-black text-white shadow-lg shadow-black/10' : 'bg-white border border-gray-100 text-gray-600 hover:border-black hover:text-black' ?>">Barchasi</a>
                <?php foreach($categories as $c): ?>
                <a hx-get="search.php?cat=<?= $c['id'] ?>" hx-target="#page-content" hx-push-url="true" 
                   class="px-5 py-3 rounded-2xl font-bold text-sm transition-all <?= $cat_id == $c['id'] ? 'bg-black text-white shadow-lg shadow-black/10' : 'bg-white border border-gray-100 text-gray-600 hover:border-black hover:text-black' ?>">
                    <?= $c['name'] ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center gap-4 bg-white border border-gray-100 rounded-2xl p-1.5 shadow-sm">
                <span class="pl-3 text-xs font-black uppercase text-gray-400 tracking-tighter">Saralash</span>
                <select name="sort" 
                        hx-get="search.php" 
                        hx-target="#search-results" 
                        hx-include="[name='q'], [name='cat']"
                        hx-push-url="true"
                        class="bg-gray-50 border-none rounded-xl px-4 py-2 text-sm font-bold focus:ring-0 outline-none cursor-pointer hover:bg-gray-100 transition-colors">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>✨ Yangi</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>💸 Arzon</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>💎 Qimmat</option>
                    <option value="alpha" <?= $sort == 'alpha' ? 'selected' : '' ?>>🔠 Alifbo</option>
                    <option value="discount" <?= $sort == 'discount' ? 'selected' : '' ?>>🔥 Chegirma</option>
                </select>
            </div>
        </div>
    </div>

    <div id="search-results">
        <?php require_once 'views/partials/search_results.php'; ?>
    </div>
</div>
