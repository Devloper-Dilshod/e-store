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
<div id="variant-modal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4 px-0 py-0 animate__animated animate__fadeIn" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="bg-white w-full sm:w-[450px] sm:rounded-[2.5rem] rounded-t-[2.5rem] shadow-2xl overflow-hidden relative animate__animated animate__slideInUp animate__faster"
         @click.outside="document.getElementById('variant-modal').remove()">
        
        <!-- Header -->
        <div class="relative h-48 bg-slate-100">
            <img src="image.php?id=<?= $product['file_id'] ?>" class="w-full h-full object-contain p-4 mix-blend-multiply">
            <button onclick="document.getElementById('variant-modal').remove()" class="absolute top-4 right-4 w-8 h-8 bg-black/5 rounded-full flex items-center justify-center hover:bg-black hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 md:p-8 space-y-6">
            <div>
                <h3 class="font-black text-xl uppercase tracking-tighter leading-none mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Variant tanlang</p>
            </div>

            <form hx-post="api/add_to_cart.php" hx-swap="outerHTML" hx-target="#variant-modal" class="space-y-6">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                
                <div class="grid gap-3 max-h-[40vh] overflow-y-auto no-scrollbar pr-1">
                    <?php foreach($variants as $idx => $v): ?>
                    <label class="relative group cursor-pointer block">
                        <input type="radio" name="variant_id" value="<?= $v['id'] ?>" class="peer sr-only" <?= $idx === 0 ? 'checked' : '' ?>>
                        <div class="flex items-center justify-between p-4 rounded-2xl border-2 border-slate-100 peer-checked:border-black peer-checked:bg-slate-50 transition-all hover:border-slate-300">
                            <div class="flex items-center gap-4">
                                <?php if($v['file_id']): ?>
                                <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 p-1">
                                    <img src="image.php?id=<?= $v['file_id'] ?>" class="w-full h-full object-contain">
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-black text-sm uppercase"><?= htmlspecialchars($v['variant_name']) ?></div>
                                </div>
                            </div>
                            <div class="font-black text-sm"><?= number_format($v['price'], 0, ',', ' ') ?></div>
                        </div>
                        <div class="absolute inset-0 rounded-2xl ring-2 ring-black scale-95 opacity-0 peer-checked:opacity-100 peer-checked:scale-100 transition-all pointer-events-none"></div>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="flex items-center gap-4 pt-2">
                    <div class="w-32 bg-slate-100 rounded-2xl flex items-center justify-between p-2 shrink-0" x-data="{ qty: 1 }">
                        <button type="button" @click="qty > 1 && qty--" class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center font-black hover:scale-95 transition">-</button>
                        <input type="hidden" name="quantity" :value="qty">
                        <span class="font-black text-lg" x-text="qty">1</span>
                        <button type="button" @click="qty++" class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center font-black hover:scale-95 transition">+</button>
                    </div>

                    <button type="submit" class="flex-1 bg-black text-white h-14 rounded-2xl font-black uppercase tracking-widest text-xs hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-black/20">
                        Qo'shish
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
