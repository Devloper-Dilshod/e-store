<div class="max-w-4xl mx-auto space-y-12 animate__animated animate__fadeIn">
    <!-- User Profile Header (Minimalist Screenshot Style) -->
    <div class="px-4 py-8 flex items-center gap-6">
        <div class="w-16 h-16 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-900 text-xl font-black shadow-sm shrink-0">
            <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-sm md:text-base font-black uppercase tracking-[0.2em] text-slate-400 truncate leading-none">
                <?= htmlspecialchars($_SESSION['user_name']) ?>
            </h1>
        </div>
        <a href="api/logout.php" hx-boost="false" class="text-[10px] font-black uppercase tracking-widest text-red-400 hover:text-red-600 transition"> Chiqish </a>
    </div>

    <!-- Orders Section -->
    <div class="space-y-6 pb-32">
        <div class="flex items-center gap-4 px-2">
            <h2 class="text-2xl font-black uppercase tracking-tighter text-slate-800">Mening buyurtmalarim</h2>
            <div class="h-[2px] md:h-[3px] flex-1 bg-slate-100 rounded-full"></div>
        </div>

        <?php if(empty($orders)): ?>
            <div class="glass p-20 rounded-[3rem] text-center border border-white/50 shadow-inner">
                 <div class="w-24 bg-slate-50 h-24 rounded-[2.5rem] flex items-center justify-center mx-auto mb-6 text-slate-200">
                     <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                 </div>
                <p class="text-slate-300 text-lg font-black uppercase tracking-widest">Buyurtmalar mavjud emas</p>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php 
                $statusMap = [
                    'pending' => ['KUTILMOQDA', 'text-orange-500'],
                    'accepted' => ['QABUL QILINDI', 'text-blue-500'],
                    'delivered' => ['YETKAZILDI', 'text-green-500'],
                    'cancelled' => ['BEKOR QILINDI', 'text-red-500'],
                    'Pending' => ['KUTILMOQDA', 'text-orange-500'],
                    'Accepted' => ['QABUL QILINDI', 'text-blue-500'],
                    'Delivered' => ['YETKAZILDI', 'text-green-500'],
                    'Cancelled' => ['BEKOR QILINDI', 'text-red-500']
                ];
                foreach($orders as $order): 
                    $orderImages = [];
                    foreach($order['items'] as $item) {
                        $fid = $item['v_file'] ?: $item['p_file'];
                        if($fid) $orderImages[] = 'image.php?id=' . $fid;
                    }
                    if(empty($orderImages)) $orderImages[] = 'assets/no-image.png';
                    $imagesJson = json_encode($orderImages);
                    $s = $statusMap[$order['status']] ?? [$order['status'], 'text-slate-400'];
                ?>
                <div class="bg-white p-5 md:p-6 rounded-[2.5rem] shadow-sm border border-slate-50 flex flex-col group cursor-pointer hover:shadow-xl hover:scale-[1.01] transition-all duration-500"
                     x-data="{ expanded: false, currentImgIdx: 0, images: <?= $imagesJson ?>, intervalId: null }"
                     @click="expanded = !expanded"
                     x-init="if(images.length > 1) { intervalId = setInterval(() => { currentImgIdx = (currentImgIdx + 1) % images.length }, 3000); }">
                    
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 md:gap-6 flex-1 min-w-0">
                            <!-- Product Thumbnails -->
                            <div class="flex -space-x-3 md:-space-x-4 shrink-0 overflow-visible py-1">
                                <?php 
                                $preview_images = array_slice($orderImages, 0, 3);
                                foreach($preview_images as $idx => $img): 
                                ?>
                                <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-white border border-slate-100 flex items-center justify-center relative overflow-hidden shadow-sm ring-4 ring-white transition-transform group-hover:scale-110" style="z-index: <?= 10 - $idx ?>;">
                                    <img src="<?= $img ?>" class="w-full h-full object-contain p-1">
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center gap-3">
                                    <span class="bg-slate-100 text-slate-500 text-[10px] font-black px-2 py-0.5 rounded-lg uppercase shadow-sm">#<?= $order['id'] ?></span>
                                    <span class="text-[10px] font-black <?= $s[1] ?> uppercase tracking-tighter"><?= $s[0] ?></span>
                                </div>
                                <div class="text-lg md:text-2xl font-black text-slate-900 tracking-tighter leading-none"><?= number_format($order['total_price'], 0, ',', ' ') ?> <span class="text-xs font-bold text-slate-400">so'm</span></div>
                                <div class="text-[10px] font-bold text-slate-300 uppercase tracking-widest"><?= date('d.m.Y', strtotime($order['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Details Accordion -->
                    <div x-show="expanded" x-collapse @click.stop class="w-full">
                        <div class="mt-6 pt-6 border-t border-slate-50 space-y-4">
                            <div class="grid gap-3 mb-6">
                                <?php foreach($order['items'] as $item): ?>
                                <div class="flex items-center gap-4 bg-slate-50/50 p-3 rounded-[1.5rem] border border-slate-100">
                                    <div class="w-12 h-12 rounded-xl bg-white p-1.5 flex items-center justify-center shrink-0 shadow-sm border border-slate-100">
                                        <img src="image.php?id=<?= $item['v_file'] ?: $item['p_file'] ?>" class="w-full h-full object-contain">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-black text-slate-900 uppercase truncate"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <div class="flex justify-between items-center mt-1">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase"><?= number_format($item['price'], 0, ',', ' ') ?> so'm</span>
                                            <span class="text-[10px] bg-white px-2 py-0.5 rounded-lg border border-slate-100 font-black">x<?= $item['quantity'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="p-5 bg-slate-900 rounded-[2rem] text-white/90 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                                <div class="relative z-10">
                                    <div class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 flex items-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                        Yetkazib berish manzili
                                    </div>
                                    <p class="text-xs font-medium leading-relaxed italic opacity-80"><?= htmlspecialchars($order['address']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
