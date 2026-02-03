            </div> <!-- end of #page-content -->

            <!-- Mobile Bottom Nav -->
            <nav class="lg:hidden fixed bottom-4 left-4 right-4 z-50 glass rounded-[2.5rem] shadow-2xl border border-white/50 p-2 flex items-center justify-around" style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));">
                <a hx-get="index.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-16 h-16 rounded-[1.8rem] transition-all duration-300 active:scale-90"
                   :class="$store.nav.active == 'index' ? 'bg-black text-white shadow-lg shadow-black/20' : 'text-slate-400 hover:text-black'"
                   @click="$store.nav.active = 'index'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </a>
                <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-16 h-16 rounded-[1.8rem] transition-all duration-300 active:scale-90"
                   :class="$store.nav.active == 'search' ? 'bg-black text-white shadow-lg shadow-black/20' : 'text-slate-400 hover:text-black'"
                   @click="$store.nav.active = 'search'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </a>
                <a hx-get="cart.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-16 h-16 rounded-[1.8rem] transition-all duration-300 relative"
                   :class="$store.nav.active == 'cart' ? 'bg-black text-white shadow-lg shadow-black/20' : 'text-slate-400 hover:text-black'"
                   @click="$store.nav.active = 'cart'">
                   <div class="relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span id="cart-badge-mobile" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-black animate__animated animate__bounceIn <?= empty($_SESSION['cart']) ? 'hidden' : '' ?>">
                            <?= count($_SESSION['cart'] ?? []) ?>
                        </span>
                   </div>
                </a>
                <a hx-get="profile.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-16 h-16 rounded-[1.8rem] transition-all duration-300 active:scale-90"
                   :class="$store.nav.active == 'profile' ? 'bg-black text-white shadow-lg shadow-black/20' : 'text-slate-400 hover:text-black'"
                   @click="$store.nav.active = 'profile'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </a>
            </nav>

            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.store('nav', {
                        active: '<?= basename($_SERVER['PHP_SELF'], '.php') == 'index' ? 'index' : basename($_SERVER['PHP_SELF'], '.php') ?>'
                    })
                })

                // Sync navigation when HTMX changes the URL or content
                const syncNav = () => {
                    const path = window.location.pathname;
                    const page = path.split('/').pop().split('?')[0].replace('.php', '') || 'index';
                    if (window.Alpine) {
                        Alpine.store('nav').active = page;
                    }
                };

                document.addEventListener('htmx:pushedIntoHistory', syncNav);
                window.addEventListener('popstate', syncNav);
                
                document.addEventListener('htmx:afterSwap', (e) => {
                    if (e.detail.target.id === 'page-content') {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        syncNav();
                    }
                });
            </script>
        </main>
    </div>
</body>
</html>
