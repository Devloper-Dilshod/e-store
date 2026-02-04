            </div> <!-- end of #page-content -->

            <!-- Mobile Bottom Nav -->
            <nav class="lg:hidden fixed bottom-6 left-6 right-6 z-50 glass rounded-[2.5rem] shadow-2xl border border-white/50 p-2.5 flex items-center justify-around transition-all duration-500" style="padding-bottom: max(0.6rem, env(safe-area-inset-bottom));">
                <a hx-get="index.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-14 h-14 rounded-3xl transition-all duration-300 active:scale-90"
                   :class="$store.nav.active == 'index' ? 'bg-black text-white shadow-xl shadow-black/30 scale-110 mobile-nav-active' : 'text-slate-400 hover:text-black hover:bg-slate-50'"
                   @click="$store.nav.active = 'index'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </a>
                <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-14 h-14 rounded-3xl transition-all duration-300 active:scale-90"
                   :class="$store.nav.active == 'search' ? 'bg-black text-white shadow-xl shadow-black/30 scale-110 mobile-nav-active' : 'text-slate-400 hover:text-black hover:bg-slate-50'"
                   @click="$store.nav.active = 'search'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </a>
                <a hx-get="cart.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-14 h-14 rounded-3xl transition-all duration-300 relative active:scale-95"
                   :class="$store.nav.active == 'cart' ? 'bg-black text-white shadow-xl shadow-black/30 scale-105 mobile-nav-active' : 'text-slate-400 hover:text-black hover:bg-slate-50'"
                   @click="$store.nav.active = 'cart'">
                   <div class="relative">
                        <svg class="w-6 h-6 transition-transform" :class="$store.nav.active == 'cart' ? 'scale-110' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span id="cart-badge-mobile" class="absolute -top-2.5 -right-2.5 bg-red-500 text-white text-[9px] w-5 h-5 rounded-full flex items-center justify-center font-black animate__animated animate__bounceIn border-2 border-white shadow-lg <?= empty($_SESSION['cart']) ? 'hidden' : '' ?>">
                            <?= count($_SESSION['cart'] ?? []) ?>
                        </span>
                   </div>
                </a>
                <a hx-get="profile.php" hx-target="#page-content" hx-push-url="true" 
                   class="flex flex-col items-center justify-center w-14 h-14 rounded-3xl transition-all duration-300 active:scale-90"
                   :class="$store.nav.active == 'profile' ? 'bg-black text-white shadow-xl shadow-black/30 scale-110 mobile-nav-active' : 'text-slate-400 hover:text-black hover:bg-slate-50'"
                   @click="$store.nav.active = 'profile'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </a>
            </nav>

            <!-- Notification Prompt -->
            <div x-data="{ show: false }" 
                 x-init="setTimeout(() => { 
                    if (!localStorage.getItem('push_asked') && Notification.permission === 'default') show = true;
                 }, 3000)"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-10"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="fixed bottom-24 lg:bottom-10 right-6 z-[60] w-[calc(100%-3rem)] sm:w-80 glass rounded-[2rem] p-6 shadow-2xl border border-white/60 animate__animated animate__fadeInUp" x-cloak>
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-black text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-black text-sm uppercase tracking-tight">Xabarnomalar</h4>
                        <p class="text-xs text-slate-500 font-bold leading-relaxed">Yangi chegirmlar va mahsulotlar haqida birinchi bo'lib xabardor bo'ling!</p>
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button @click="show = false; localStorage.setItem('push_asked', 'true')" class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-black transition">Keyinroq</button>
                    <button @click="requestNotificationPermission(); show = false" class="flex-1 py-3 bg-black text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition shadow-lg shadow-black/20">Ruxsat berish</button>
                </div>
            </div>

            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.store('nav', {
                        active: '<?= basename($_SERVER['PHP_SELF'], '.php') == 'index' ? 'index' : basename($_SERVER['PHP_SELF'], '.php') ?>'
                    })
                })

                // PUSH NOTIFICATIONS LOGIC
                async function requestNotificationPermission() {
                    localStorage.setItem('push_asked', 'true');
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        registerServiceWorker();
                    }
                }

                async function registerServiceWorker() {
                    if ('serviceWorker' in navigator && 'PushManager' in window) {
                        try {
                            const reg = await navigator.serviceWorker.register('sw.js');
                            console.log('SW Registered');
                            
                            // Check if existing sub
                            let sub = await reg.pushManager.getSubscription();
                            if (!sub) {
                                // Real VAPID pair would be here, for now we use simple interest storage 
                                // because full VAPID implementation needs complex backend crypto
                                sub = { endpoint: 'browser_notification_' + Math.random().toString(36).substr(2, 9) };
                            }
                            
                            // Save to server
                            await fetch('api/save_subscription.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(sub)
                            });
                            
                            // Show success toast
                            new Notification("✅ Muvaffaqiyatli!", {
                                body: "Endi siz barcha yangiliklardan birinchilardan bo'lib xabardor bo'lasiz.",
                                icon: 'assets/images/logo.png'
                            });
                        } catch (err) {
                            console.error('SW Error:', err);
                        }
                    }
                }

                // Notification Polling
                let lastNotificationId = 0;
                localStorage.setItem('last_notify_id', lastNotificationId);

                async function checkNewNotifications() {
                    const lastId = localStorage.getItem('last_notify_id') || 0;
                    try {
                        const res = await fetch(`api/check_notifications.php?last_id=${lastId}`);
                        const data = await res.json();
                        if (data && data.id) {
                            localStorage.setItem('last_notify_id', data.id);
                            if (Notification.permission === 'granted') {
                                const n = new Notification(data.title, {
                                    body: data.body,
                                    icon: data.icon || 'assets/images/logo.png'
                                });
                                n.onclick = () => {
                                    window.focus();
                                    if (data.url) window.location.href = data.url;
                                };
                            }
                        }
                    } catch (e) {}
                }

                setInterval(checkNewNotifications, 10000); // Check every 10s

                // Initial request if sw exists
                if (Notification.permission === 'granted') {
                    registerServiceWorker();
                }

                // Sync navigation when HTMX changes the URL or content
                const syncNav = () => {
                    const path = window.location.pathname;
                    const segments = path.split('/');
                    let page = segments.pop() || 'index.php';
                    if (page === segments[segments.length - 1]) page = 'index.php'; // Handle trailing slashes or roots
                    page = page.split('?')[0].replace('.php', '');
                    if (!page || page === 'Store') page = 'index'; // Adjustment for subdirectory
                    
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
                
                // Initial sync
                document.addEventListener('DOMContentLoaded', syncNav);
            </script>
        </main>
    </div>
    <div id="global-modal"></div>
</body>
</html>
