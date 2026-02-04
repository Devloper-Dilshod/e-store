<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-STORE</title>
    <!-- Premium UI Tools -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/collapse.min.js"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <style>
        :root { --accent: #000; --bg: #f8fafc; }
        html, body { margin: 0; padding: 0; min-height: 100vh; width: 100%; overflow-x: hidden; scroll-behavior: smooth; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg); color: #0f172a; -webkit-font-smoothing: antialiased; }

        @media (max-width: 1023px) {
            #main-sidebar { display: none !important; }
        }

        .glass { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.4); }
        .glass-dark { background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        
        /* Mobile Enhancements */
        @media (max-width: 640px) {
            * { -webkit-tap-highlight-color: transparent; }
            .glass { backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
        }
        
        button, a { touch-action: manipulation; }
        
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
        
        /* HTMX Transitions */
        .htmx-indicator { display: none; }
        .htmx-request .htmx-indicator { display: block; }
        .htmx-swapping { opacity: 0; transition: opacity 0.3s ease-out; }
        .htmx-added { opacity: 0; animation: fadeIn 0.4s ease-out forwards; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        #progress-bar { position: fixed; top: 0; left: 0; width: 0%; height: 5px; background: linear-gradient(90deg, #000, #444, #000); background-size: 200% 100%; animation: shimmer 2s infinite linear; z-index: 9999; transition: width 0.4s ease; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        @keyframes shimmer { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
        .htmx-request #progress-bar { width: 45%; }
        .htmx-request.htmx-settling #progress-bar { width: 100%; }
        
        #cart-badge:not(:empty), #cart-badge-mobile:not(:empty) { display: flex !important; }
        #cart-badge:empty, #cart-badge-mobile:empty { display: none !important; }

        @keyframes badgeBounce { 
            0% { transform: scale(1); }
            50% { transform: scale(1.6); }
            100% { transform: scale(1); }
        }
        .badge-update { animation: badgeBounce 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        
        .skeleton { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: skeleton 1.5s infinite linear; }
        @keyframes skeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        .nav-link-hover { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .nav-link-hover:hover { transform: scale(1.05) translateX(8px); background: #f1f5f9; color: black; }
        .nav-link-hover:active { transform: scale(0.95); }
        .nav-link-hover svg { transition: all 0.4s ease; }
        .nav-link-hover:hover svg { transform: scale(1.2) rotate(-5deg); color: #000; }
        
        /* Mobile Nav Pulsing Effect */
        .mobile-nav-active { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3); border: 2px solid rgba(255,255,255,0.5); }
    </style>
</head>
<body class="antialiased selection:bg-black selection:text-white">
    <div id="progress-bar"></div>

    <div class="flex min-h-screen w-full relative">
        <!-- Sidebar (Desktop) - Fixed Position -->
        <aside id="main-sidebar" class="hidden lg:flex w-72 bg-white border-r border-gray-100 flex-col px-6 py-8 h-screen fixed top-0 left-0 z-50 shrink-0">
            <div class="mb-10 px-4">
                <?php global $store_name; ?>
                <a hx-get="index.php" hx-target="#page-content" hx-push-url="true" class="text-3xl font-black tracking-tighter uppercase whitespace-normal break-words leading-none">
                    <?= htmlspecialchars($store_name) ?><span class="text-slate-200">.</span>
                </a>
            </div>

            <nav class="space-y-3 flex-1">
                <a hx-get="index.php" hx-target="#page-content" hx-push-url="true" 
                   class="nav-link-hover flex items-center gap-4 px-4 py-4 rounded-2xl transition font-black uppercase text-xs tracking-widest active:scale-95"
                   :class="$store.nav.active == 'index' ? 'bg-black text-white shadow-xl shadow-black/20' : 'hover:bg-gray-100 text-gray-400 hover:text-black'"
                   @click="$store.nav.active = 'index'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Asosiy</span>
                </a>
                <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" 
                   class="nav-link-hover flex items-center gap-4 px-4 py-4 rounded-2xl transition font-black uppercase text-xs tracking-widest active:scale-95"
                   :class="$store.nav.active == 'search' ? 'bg-black text-white shadow-xl shadow-black/20' : 'hover:bg-gray-100 text-gray-400 hover:text-black'"
                   @click="$store.nav.active = 'search'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Qidiruv</span>
                </a>
                <a hx-get="cart.php" hx-target="#page-content" hx-push-url="true" 
                   class="nav-link-hover flex items-center gap-4 px-4 py-4 rounded-2xl transition font-black uppercase text-xs tracking-widest active:scale-95"
                   :class="$store.nav.active == 'cart' ? 'bg-black text-white shadow-xl shadow-black/20' : 'hover:bg-gray-100 text-gray-400 hover:text-black'"
                   @click="$store.nav.active = 'cart'">
                    <div class="relative">
                        <svg class="w-5 h-5 transition-transform" :class="$store.nav.active == 'cart' ? 'scale-110' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span id="cart-badge" class="absolute -top-2.5 -right-2.5 bg-red-500 text-white text-[9px] w-5 h-5 rounded-full flex items-center justify-center font-black animate__animated animate__bounceIn border-2 border-white shadow-sm <?= empty($_SESSION['cart']) ? 'hidden' : '' ?>">
                            <?= count($_SESSION['cart'] ?? []) ?>
                        </span>
                    </div>
                    <span>Savatcha</span>
                </a>
            </nav>

            <div class="mt-auto pt-8 border-t border-gray-100">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a hx-get="profile.php" hx-target="#page-content" hx-push-url="true" 
                       class="nav-link-hover flex items-center gap-4 px-4 py-4 rounded-2xl transition font-black uppercase text-xs tracking-widest active:scale-95 mb-2"
                       :class="$store.nav.active == 'profile' ? 'bg-black text-white shadow-xl shadow-black/20' : 'hover:bg-gray-100 text-gray-400 hover:text-black'"
                       @click="$store.nav.active = 'profile'">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-black text-slate-900 overflow-hidden shadow-inner group-hover:scale-110 transition">
                            <?= mb_substr($_SESSION['user_name'], 0, 1) ?>
                        </div>
                        <span class="truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="block bg-black text-white text-center py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl shadow-black/20 hover:scale-[1.05] active:scale-95 transition-all">Kirish</a>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 min-w-0 relative lg:ml-72">
            <!-- Mobile Header (Simplified) -->
            <header class="lg:hidden flex items-center justify-between px-6 py-4 bg-white/70 backdrop-blur-xl sticky top-0 z-40 border-b border-white/20 shadow-[0_4px_30px_rgba(0,0,0,0.03)]">
                <a hx-get="index.php" hx-target="#page-content" hx-push-url="true" class="text-2xl font-black tracking-tighter hover:opacity-70 transition group">
                    <?php global $store_name; ?>
                    <span class="inline-block group-hover:scale-105 transition uppercase"><?= htmlspecialchars($store_name) ?>.</span>
                </a>
                <div class="flex items-center gap-3">
                    <a hx-get="search.php" hx-target="#page-content" hx-push-url="true" class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-500 hover:text-black hover:bg-slate-100 transition active:scale-90">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </a>
                </div>
            </header>

            <div id="page-content" class="pb-48 lg:pb-12 pt-6 px-4 md:px-8 min-h-[80vh]">
