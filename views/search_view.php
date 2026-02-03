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

        <input type="hidden" name="cat" value="<?= $cat_id ?>">

        <div class="flex flex-wrap gap-2">
            <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" class="px-5 py-3 rounded-2xl font-bold text-sm transition-all <?= !$cat_id ? 'bg-black text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">Barchasi</a>
            <?php foreach($categories as $c): ?>
            <a hx-get="search.php?cat=<?= $c['id'] ?>" hx-target="#page-content" hx-push-url="true" 
               class="px-5 py-3 rounded-2xl font-bold text-sm transition-all <?= $cat_id == $c['id'] ? 'bg-black text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                <?= $c['name'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="search-results">
        <?php require_once 'views/partials/search_results.php'; ?>
    </div>
</div>
