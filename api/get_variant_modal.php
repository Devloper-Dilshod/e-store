<?php
require_once '../core/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) exit;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) exit;

$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
$stmt->execute([$id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no variants, just show add to cart confirmation or nothing (shouldn't happen if logic is correct)
if (empty($variants)) {
    // Fallback: Just return a script to trigger add to cart directly? 
    // Or display a simple "Add" modal.
    // For now, assume this is only called if variants exist.
}

?>
<div id="variant-modal-centered" 
     x-data="{ 
        selectedVariant: <?= (int)$variants[0]['id'] ?>,
        productPrice: <?= (float)$variants[0]['price'] ?>,
        hasDiscount: <?= $product['has_discount'] ? 'true' : 'false' ?>,
        discountPercent: <?= (int)$product['discount_percent'] ?>,
        currentImage: 'image.php?id=<?= $variants[0]['file_id'] ?: $product['file_id'] ?>',
        
        get displayPrice() {
            if (!this.hasDiscount) return this.productPrice;
            return Math.round(this.productPrice * (1 - this.discountPercent / 100));
        },
        
        updateSelected(id, price, imgId) {
            this.selectedVariant = id;
            this.productPrice = price;
            if(imgId) this.currentImage = 'image.php?id=' + imgId;
        }
     }"
     style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; display: grid; place-items: center; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    
    <div class="bg-white w-full max-w-[450px] rounded-[2.5rem] shadow-2xl overflow-hidden relative animate__animated animate__zoomIn animate__faster"
         @click.outside="document.getElementById('variant-modal-centered').remove()">
        
        <!-- Header Image -->
        <div class="relative h-64 bg-slate-50 flex items-center justify-center p-8 transition-all duration-500">
            <img :src="currentImage" class="w-full h-full object-contain mix-blend-multiply animate__animated animate__fadeIn">
            
            <button onclick="document.getElementById('variant-modal-centered').remove()" class="absolute top-6 right-6 w-10 h-10 bg-white/50 backdrop-blur-md rounded-full flex items-center justify-center hover:bg-black hover:text-white transition shadow-sm active:scale-90">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Discount Badge inside Modal -->
            <?php if($product['has_discount']): ?>
            <div class="absolute top-6 left-6 bg-red-600 text-white px-4 py-2 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-red-500/30">
                -<?= $product['discount_percent'] ?>%
            </div>
            <?php endif; ?>
        </div>

        <div class="p-8 space-y-8">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h3 class="font-black text-2xl uppercase tracking-tighter leading-tight mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Variant tanlang</p>
                </div>
                <!-- Interactive Price Display -->
                <div class="text-right">
                    <template x-if="hasDiscount">
                        <p class="text-[10px] text-slate-300 line-through font-bold mb-1" x-text="new Intl.NumberFormat().format(productPrice)"></p>
                    </template>
                    <p class="text-2xl font-black text-slate-900 tracking-tighter" x-text="new Intl.NumberFormat().format(displayPrice) + ' s.'"></p>
                </div>
            </div>

            <form hx-post="api/add_to_cart.php" hx-swap="outerHTML" hx-target="#variant-modal-centered" class="space-y-8">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="variant_id" :value="selectedVariant">
                
                <div class="grid gap-3 max-h-[35vh] overflow-y-auto no-scrollbar pr-1">
                    <?php foreach($variants as $idx => $v): ?>
                    <label class="relative group cursor-pointer block">
                        <input type="radio" name="temp_v" value="<?= $v['id'] ?>" class="peer sr-only" 
                               @change="updateSelected(<?= $v['id'] ?>, <?= $v['price'] ?>, '<?= $v['file_id'] ?>')"
                               <?= $idx === 0 ? 'checked' : '' ?>>
                        <div class="flex items-center justify-between p-5 rounded-3xl border-2 border-slate-100 peer-checked:border-black peer-checked:bg-slate-50 transition-all duration-300 group-hover:border-slate-300">
                            <div class="flex items-center gap-4">
                                <?php if($v['file_id']): ?>
                                <div class="w-12 h-12 rounded-2xl bg-white border border-slate-100 p-1.5 shrink-0 shadow-sm transition-transform group-hover:scale-105">
                                    <img src="image.php?id=<?= $v['file_id'] ?>" class="w-full h-full object-contain">
                                </div>
                                <?php else: ?>
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-black text-sm uppercase tracking-tight"><?= htmlspecialchars($v['variant_name'] ?: 'Tanlanmagan') ?></div>
                                    <div class="text-[9px] font-bold text-slate-400 uppercase mt-0.5" x-show="hasDiscount">Chegirma bilan</div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <?php if($product['has_discount']): 
                                    $v_disc_price = round($v['price'] * (1 - $product['discount_percent'] / 100));
                                ?>
                                    <div class="text-[9px] text-slate-300 line-through font-bold mb-0.5"><?= number_format($v['price'], 0, ',', ' ') ?></div>
                                    <div class="font-black text-sm text-red-600"><?= number_format($v_disc_price, 0, ',', ' ') ?> <span class="text-[9px]">s.</span></div>
                                <?php else: ?>
                                    <div class="font-black text-sm text-slate-900"><?= number_format($v['price'], 0, ',', ' ') ?> <span class="text-[9px]">s.</span></div>
                                <?php endif; ?>
                                <div class="w-2 h-2 rounded-full bg-black scale-0 peer-checked:scale-100 transition-transform mt-2"></div>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center gap-4 pt-4 border-t border-slate-50">
                    <div class="w-full sm:w-36 bg-slate-100 rounded-[1.5rem] flex items-center justify-between p-1.5 shrink-0" x-data="{ qty: 1 }">
                        <button type="button" @click="qty > 1 && qty--" class="w-11 h-11 bg-white rounded-xl shadow-sm flex items-center justify-center font-black hover:bg-slate-50 active:scale-90 transition">-</button>
                        <input type="hidden" name="quantity" :value="qty">
                        <span class="font-black text-xl w-10 text-center" x-text="qty">1</span>
                        <button type="button" @click="qty++" class="w-11 h-11 bg-white rounded-xl shadow-sm flex items-center justify-center font-black hover:bg-slate-50 active:scale-90 transition">+</button>
                    </div>

                    <button type="submit" class="w-full h-14 bg-black text-white rounded-[1.5rem] font-black uppercase tracking-widest text-[11px] hover:scale-[1.02] active:scale-95 transition-all shadow-2xl shadow-black/20 group">
                        <span class="flex items-center justify-center gap-3">
                            <svg class="w-5 h-5 group-active:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Savatchaga qo'shish
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
