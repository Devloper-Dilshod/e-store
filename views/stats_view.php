<div class="max-w-7xl mx-auto space-y-12 animate__animated animate__fadeIn">
    
    <!-- Hero Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Daily -->
        <div class="glass p-8 rounded-[3rem] border border-white/60 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 rounded-full -mr-16 -mt-16 blur-3xl transition-all group-hover:bg-orange-500/20"></div>
            <div class="relative z-10 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-orange-600 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Bugungi tushum</p>
                    <h3 class="text-3xl font-black tracking-tighter text-slate-900"><?= number_format($daily['total'] ?? 0, 0, ',', ' ') ?> <span class="text-xs">so'm</span></h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-2"><?= $daily['count'] ?> ta buyurtma</p>
                </div>
            </div>
        </div>

        <!-- Weekly -->
        <div class="glass p-8 rounded-[3rem] border border-white/60 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -mr-16 -mt-16 blur-3xl transition-all group-hover:bg-blue-500/20"></div>
            <div class="relative z-10 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-600 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Haftalik tushum</p>
                    <h3 class="text-3xl font-black tracking-tighter text-slate-900"><?= number_format($weekly['total'] ?? 0, 0, ',', ' ') ?> <span class="text-xs">so'm</span></h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-2"><?= $weekly['count'] ?> ta buyurtma</p>
                </div>
            </div>
        </div>

        <!-- Monthly -->
        <div class="bg-black p-8 rounded-[3rem] shadow-2xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-3xl transition-all group-hover:bg-white/20"></div>
            <div class="relative z-10 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-white shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Oylik tushum</p>
                    <h3 class="text-3xl font-black tracking-tighter text-white"><?= number_format($monthly['total'] ?? 0, 0, ',', ' ') ?> <span class="text-xs">so'm</span></h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-2"><?= $monthly['count'] ?> ta buyurtma</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders History with Pagination -->
    <div class="space-y-8">
        <div class="flex items-center gap-6 px-4">
            <h2 class="text-3xl font-black uppercase tracking-tighter text-slate-800">Barcha buyurtmalar</h2>
            <div class="h-[3px] flex-1 bg-slate-100 rounded-full"></div>
        </div>

        <div class="grid gap-4">
            <?php foreach($orders as $order): 
                $status_map = [
                    'pending' => ['label' => 'Kutilmoqda', 'class' => 'bg-orange-50 text-orange-600 border-orange-100'],
                    'accepted' => ['label' => 'Tasdiqlandi', 'class' => 'bg-green-50 text-green-600 border-green-100'],
                    'completed' => ['label' => 'Yetkazildi', 'class' => 'bg-blue-50 text-blue-600 border-blue-100'],
                    'cancelled' => ['label' => 'Bekor qilindi', 'class' => 'bg-red-50 text-red-600 border-red-100'],
                    'rejected' => ['label' => 'Rad etildi', 'class' => 'bg-red-50 text-red-600 border-red-100']
                ];
                $s = $status_map[$order['status']] ?? ['label' => $order['status'], 'class' => 'bg-slate-50 text-slate-600 border-slate-100'];
            ?>
            <div class="glass p-4 md:p-5 rounded-[2.5rem] border border-white/60 flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:border-slate-300 transition-all duration-500">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-900 font-black text-[10px] shadow-sm group-hover:scale-110 transition shrink-0">
                        #<?= $order['id'] ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                        <h4 class="text-sm md:text-base font-black text-slate-900 tracking-tight leading-none truncate"><?= htmlspecialchars($order['customer_name'] ?: 'Noma'lum') ?></h4>
                        <p class="text-[9px] font-bold text-slate-400 uppercase mt-1"><?= $order['customer_phone'] ?></p>
                    </div>
                </div>

                <div class="flex items-center justify-between md:justify-end gap-4 md:gap-8 border-t md:border-t-0 border-slate-100 pt-3 md:pt-0">
                    <div class="text-left md:text-right">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Summa</p>
                        <p class="text-base md:text-lg font-black text-slate-900 leading-none"><?= number_format($order['total_price'], 0, ',', ' ') ?> <span class="text-[9px] opacity-50">sh.</span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1.5 rounded-xl text-[8px] font-black uppercase tracking-widest border <?= $s['class'] ?>">
                            <?= $s['label'] ?>
                        </span>
                        <a hx-get="profile.php" hx-target="#page-content" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-black hover:text-white transition-all cursor-pointer shrink-0 active:scale-90">
                            <svg class="w-5 h-5 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="flex justify-center gap-3 pt-12 pb-20">
            <?php for($i=1; $i<=$total_pages; $i++): ?>
            <button hx-get="stats.php?page=<?= $i ?>" hx-target="#page-content" hx-push-url="true"
                    class="w-12 h-12 rounded-2xl font-black text-xs transition-all <?= $i == $current_page ? 'bg-black text-white shadow-xl shadow-black/20' : 'bg-white text-slate-400 hover:bg-slate-50 border border-slate-100' ?>">
                <?= $i ?>
            </button>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
