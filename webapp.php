<?php
require_once 'core/config.php';
require_once 'core/render.php';

// Handle Telegram WebApp auto-login
$tg_auth_error = false;
if (isset($_POST['tg_init_data']) && !isset($_SESSION['user_id'])) {
    $init_data = $_POST['tg_init_data'];
    $tg_data = verifyTelegramWebAppData($init_data);
    if ($tg_data) {
        $user_data = json_decode($tg_data['user'] ?? '{}', true);
        if ($user_data && isset($user_data['id'])) {
            $tg_id = (string)$user_data['id'];
            $name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')) ?: 'Foydalanuvchi';
            $username = $user_data['username'] ?? '';

            $check = $pdo->prepare("SELECT id, is_blocked FROM users WHERE telegram_id=?");
            $check->execute([$tg_id]);
            $user = $check->fetch();

            if ($user && $user['is_blocked']) {
                $tg_auth_error = 'blocked';
            } else {
                if ($user) {
                    $pdo->prepare("UPDATE users SET name=?, username=? WHERE telegram_id=?")->execute([$name, $username, $tg_id]);
                    $uid = $user['id'];
                } else {
                    $pdo->prepare("INSERT INTO users (telegram_id, name, username) VALUES (?,?,?)")->execute([$tg_id, $name, $username]);
                    $uid = $pdo->lastInsertId();
                }
                $_SESSION['user_id'] = $uid;
                $_SESSION['telegram_id'] = $tg_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['is_admin'] = isUserAdmin($tg_id);
                // Save init_data cookie for future requests
                setcookie('tg_uid', $uid, time() + 86400 * 30, '/', '', false, true);
            }
        }
    } else {
        $tg_auth_error = 'invalid';
    }
}

// Determine page
$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'catalog', 'product', 'cart', 'checkout', 'orders', 'profile'];
if (!in_array($page, $allowed_pages)) $page = 'home';

// Fetch needed data
$store_name_val = getSetting('store_name', $store_name);
$welcome_msg = getSetting('welcome_message', 'Xush kelibsiz!');

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$posters = $pdo->query("SELECT * FROM posters ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$products = [];
$total_pages_prod = 1;
$current_page_prod = max(1, (int)($_GET['p'] ?? 1));
$limit_prod = 12;

if ($page === 'home' || $page === 'catalog') {
    $cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $offset_prod = ($current_page_prod - 1) * $limit_prod;

    $where = "1=1";
    $params = [];
    if ($cat_filter) { $where .= " AND p.category_id=?"; $params[] = $cat_filter; }
    if ($q) { $where .= " AND p.name LIKE ?"; $params[] = "%$q%"; }

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where");
    $count_stmt->execute($params);
    $total_prod = $count_stmt->fetchColumn();
    $total_pages_prod = max(1, ceil($total_prod / $limit_prod));

    $stmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM product_variants WHERE product_id=p.id) as variant_count FROM products p WHERE $where ORDER BY p.id DESC LIMIT $limit_prod OFFSET $offset_prod");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$product_detail = null;
if ($page === 'product' && isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?");
    $stmt->execute([$pid]);
    $product_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product_detail) {
        $vs = $pdo->prepare("SELECT * FROM product_variants WHERE product_id=?");
        $vs->execute([$pid]);
        $product_detail['variants'] = $vs->fetchAll(PDO::FETCH_ASSOC);
    }
}

$orders_list = [];
$orders_total_pages = 1;
$orders_current_page = max(1, (int)($_GET['op'] ?? 1));
if (($page === 'orders' || $page === 'profile') && isset($_SESSION['user_id'])) {
    $o_limit = 10;
    $o_offset = ($orders_current_page - 1) * $o_limit;
    $o_total = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
    $o_total->execute([$_SESSION['user_id']]);
    $orders_total_pages = max(1, ceil($o_total->fetchColumn() / $o_limit));
    $o_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT $o_limit OFFSET $o_offset");
    $o_stmt->execute([$_SESSION['user_id']]);
    $orders_list = $o_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orders_list as &$ord) {
        $oi = $pdo->prepare("SELECT oi.*, p.name as pname, p.image_path as p_img, pv.variant_name, pv.image_path as v_img FROM order_items oi JOIN products p ON oi.product_id=p.id LEFT JOIN product_variants pv ON oi.variant_id=pv.id WHERE oi.order_id=?");
        $oi->execute([$ord['id']]);
        $ord['items'] = $oi->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($ord);
}

$webapp_url = getSetting('webapp_url', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/Store/webapp.php');
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= htmlspecialchars($store_name_val) ?></title>
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<script>
    const tg = window.Telegram?.WebApp;
    if (!tg || !tg.initData) {
        document.documentElement.innerHTML = '<body style="margin:0;padding:20px;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8fafc;font-family:sans-serif;text-align:center;"><div style="background:#fff;padding:40px;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.05);"><div style="font-size:48px;margin-bottom:16px;">🤖</div><h2 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Faqat Telegram orqali</h2><p style="margin:0;color:#64748b;font-size:14px;line-height:1.5;">Bu sayt faqat Telegram bot ichidagi<br>Web App orqali ishlaydi.</p></div></body>';
        window.stop();
    } else if (['web', 'weba', 'webk'].includes(tg.platform)) {
        document.documentElement.innerHTML = '<body style="margin:0;padding:20px;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8fafc;font-family:sans-serif;text-align:center;"><div style="background:#fff;padding:40px;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.05);"><div style="font-size:48px;margin-bottom:16px;">📱</div><h2 style="margin:0 0 8px;font-size:20px;color:#0f172a;">Faqat mobil ilova orqali</h2><p style="margin:0;color:#64748b;font-size:14px;line-height:1.5;">Web versiyadan foydalanish taqiqlangan.<br>Iltimos, mobil ilovadan kiring.</p></div></body>';
        window.stop();
    }
</script>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--tg-bg:var(--tg-theme-bg-color,#fff);--tg-text:var(--tg-theme-text-color,#0f172a);--tg-hint:var(--tg-theme-hint-color,#94a3b8);--tg-link:var(--tg-theme-link-color,#000);--tg-btn:var(--tg-theme-button-color,#000);--tg-btn-text:var(--tg-theme-button-text-color,#fff);--tg-secondary-bg:var(--tg-theme-secondary-bg-color,#f8fafc);}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Outfit',sans-serif;background:var(--tg-bg);color:var(--tg-text);min-height:100vh;overflow-x:hidden;}
.btn-tg{background:var(--tg-btn);color:var(--tg-btn-text);border:none;border-radius:16px;padding:14px 24px;font-family:'Outfit',sans-serif;font-weight:800;font-size:13px;text-transform:uppercase;letter-spacing:.1em;cursor:pointer;transition:all .2s;width:100%;}
.btn-tg:active{transform:scale(.97);}
.btn-outline{background:transparent;border:2px solid var(--tg-btn);color:var(--tg-btn);}
.card{background:var(--tg-secondary-bg);border-radius:20px;overflow:hidden;}
.page{display:none;animation:fadeUp .3s ease;}
.page.active{display:block;}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
.bottom-nav{position:fixed;bottom:0;left:0;right:0;background:rgba(255,255,255,.9);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-top:1px solid rgba(0,0,0,.06);padding:8px 0 max(8px,env(safe-area-inset-bottom));z-index:100;display:flex;justify-content:space-around;}
.nav-item{display:flex;flex-direction:column;align-items:center;gap:3px;padding:8px 16px;border-radius:14px;transition:all .2s;cursor:pointer;color:#94a3b8;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;min-width:60px;}
.nav-item.active{color:var(--tg-btn);}
.nav-item svg{width:22px;height:22px;}
.product-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;padding:16px;}
.product-card{background:var(--tg-secondary-bg);border-radius:20px;overflow:hidden;cursor:pointer;transition:transform .2s;}
.product-card:active{transform:scale(.97);}
.product-card img{width:100%;aspect-ratio:1;object-fit:cover;}
.badge{position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:900;display:flex;align-items:center;justify-content:center;border:2px solid var(--tg-bg);}
.shimmer{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;}
@keyframes shimmer{0%{background-position:200% 0;}100%{background-position:-200% 0;}}
.status-pending{background:#fef3c7;color:#d97706;}
.status-accepted{background:#d1fae5;color:#059669;}
.status-rejected,.status-cancelled{background:#fee2e2;color:#dc2626;}
.status-delivered{background:#dbeafe;color:#2563eb;}
.no-scrollbar::-webkit-scrollbar{display:none;}
.no-scrollbar{-ms-overflow-style:none;scrollbar-width:none;}
input,textarea,select{font-family:'Outfit',sans-serif;background:var(--tg-secondary-bg);border:1.5px solid transparent;border-radius:14px;padding:13px 16px;width:100%;font-size:14px;font-weight:500;color:var(--tg-text);outline:none;transition:border-color .2s;}
input:focus,textarea:focus{border-color:var(--tg-btn);}
</style>
</head>
<body>

<?php if ($tg_auth_error === 'blocked'): ?>
<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;text-align:center;">
    <div>
        <div style="font-size:64px;">⛔</div>
        <h2 style="font-weight:900;font-size:22px;margin:16px 0 8px;">Kirish taqiqlangan</h2>
        <p style="color:#94a3b8;font-size:14px;">Sizning hisobingiz bloklangan. Murojaat uchun adminlarga yozing.</p>
    </div>
</div>
<?php else: ?>

<!-- AUTH FORM (shown only if not logged in) -->
<?php if (!isset($_SESSION['user_id'])): ?>
<div id="auth-screen" style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="text-align:center;width:100%;max-width:320px;">
        <div style="font-size:56px;margin-bottom:16px;">🛍</div>
        <h1 style="font-weight:900;font-size:24px;margin-bottom:8px;"><?= htmlspecialchars($store_name_val) ?></h1>
        <p style="color:#94a3b8;font-size:14px;margin-bottom:32px;">Telegram orqali tizimga kiring</p>
        <form method="POST" id="tg-login-form">
            <input type="hidden" name="tg_init_data" id="tg-init-data">
            <button type="submit" class="btn-tg">🔐 Kirish</button>
        </form>
        <p style="color:#94a3b8;font-size:11px;margin-top:16px;">Xavfsiz kirish · Telegram ID asosida</p>
    </div>
</div>
<script>
const tg = window.Telegram?.WebApp;
if (tg) {
    tg.ready();
    tg.expand();
    const initData = tg.initData;
    if (initData) {
        document.getElementById('tg-init-data').value = initData;
        document.getElementById('tg-login-form').submit();
    }
}
</script>
<?php else: ?>

<!-- MAIN APP -->
<div id="app" style="padding-bottom:80px;">

<!-- ===== HOME PAGE ===== -->
<div class="page <?= $page==='home'?'active':'' ?>" id="page-home">
    <!-- Header -->
    <div style="padding:20px 16px 8px;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <p style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;"><?= htmlspecialchars($welcome_msg) ?></p>
            <h1 style="font-weight:900;font-size:22px;"><?= htmlspecialchars($store_name_val) ?></h1>
        </div>
        <div style="width:42px;height:42px;background:var(--tg-btn);border-radius:14px;display:flex;align-items:center;justify-content:center;color:var(--tg-btn-text);font-weight:900;font-size:16px;">
            <?= mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1)) ?>
        </div>
    </div>

    <!-- Posters Slider -->
    <?php if ($posters): ?>
    <div style="padding:8px 16px;" id="poster-wrap">
        <div class="no-scrollbar" style="display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;">
        <?php foreach ($posters as $pos): 
            $pimg = get_image_url($pos['image_path'] ?? $pos['file_id'] ?? '');
            $plink = $pos['link'] ?? '#';
        ?>
            <a href="<?= htmlspecialchars($plink) ?>" onclick="navToPage('catalog')" style="flex-shrink:0;width:calc(100vw - 32px);aspect-ratio:2/1;scroll-snap-align:start;border-radius:20px;overflow:hidden;display:block;">
                <img src="<?= $pimg ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Categories -->
    <div style="padding:12px 16px 0;">
        <p style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;margin-bottom:10px;">Kategoriyalar</p>
        <div class="no-scrollbar" style="display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;">
            <button onclick="filterCat(0)" id="cat-btn-0" style="flex-shrink:0;padding:8px 18px;border-radius:20px;border:none;background:var(--tg-btn);color:var(--tg-btn-text);font-family:'Outfit',sans-serif;font-weight:800;font-size:12px;cursor:pointer;">Barchasi</button>
            <?php foreach ($categories as $c): ?>
            <button onclick="filterCat(<?= $c['id'] ?>)" id="cat-btn-<?= $c['id'] ?>" style="flex-shrink:0;padding:8px 18px;border-radius:20px;border:1.5px solid #e2e8f0;background:transparent;font-family:'Outfit',sans-serif;font-weight:700;font-size:12px;cursor:pointer;color:var(--tg-text);">
                <?= htmlspecialchars($c['name']) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Products -->
    <div style="padding:12px 0 0;">
        <div style="padding:0 16px;display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <p style="font-weight:900;font-size:16px;">Mahsulotlar</p>
            <div style="position:relative;">
                <input type="text" id="search-input" placeholder="Qidirish..." onkeyup="debounceSearch(this.value)" style="padding:8px 14px;font-size:13px;border-radius:12px;background:var(--tg-secondary-bg);border:1.5px solid #e2e8f0;width:130px;">
            </div>
        </div>
        <div class="product-grid" id="products-grid">
            <?php foreach ($products as $prod): 
                $pimg = get_image_url($prod['image_path'] ?? $prod['file_id'] ?? '');
                $price = $prod['has_discount'] && $prod['discount_price'] > 0 ? $prod['discount_price'] : $prod['base_price'];
            ?>
            <div class="product-card" onclick="openProduct(<?= $prod['id'] ?>)">
                <div style="position:relative;">
                    <img src="<?= $pimg ?>" loading="lazy" style="width:100%;aspect-ratio:1;object-fit:cover;" onerror="this.src='assets/images/placeholder.png'">
                    <?php if ($prod['has_discount'] && $prod['discount_percent'] > 0): ?>
                    <span style="position:absolute;top:8px;left:8px;background:#ef4444;color:#fff;border-radius:8px;padding:2px 8px;font-size:10px;font-weight:900;">-<?= $prod['discount_percent'] ?>%</span>
                    <?php endif; ?>
                    <?php if ($prod['variant_count'] > 0): ?>
                    <span style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.7);color:#fff;border-radius:8px;padding:2px 8px;font-size:10px;font-weight:700;"><?= $prod['variant_count'] ?> xil</span>
                    <?php endif; ?>
                </div>
                <div style="padding:10px 12px 12px;">
                    <p style="font-weight:800;font-size:13px;line-height:1.3;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($prod['name']) ?></p>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-weight:900;font-size:14px;color:var(--tg-btn);"><?= number_format($price, 0, ',', ' ') ?> <small>so'm</small></span>
                        <?php if ($prod['has_discount']): ?>
                        <span style="font-size:11px;color:#94a3b8;text-decoration:line-through;"><?= number_format($prod['base_price'], 0, ',', ' ') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <div style="grid-column:span 2;text-align:center;padding:40px;color:#94a3b8;">
                <div style="font-size:40px;">🛍</div>
                <p style="font-weight:700;margin-top:8px;">Mahsulotlar yo'q</p>
            </div>
            <?php endif; ?>
        </div>
        <!-- Pagination -->
        <?php if ($total_pages_prod > 1): ?>
        <div style="display:flex;justify-content:center;gap:8px;padding:16px;">
            <?php for ($i=1; $i<=$total_pages_prod; $i++): ?>
            <button onclick="loadPage(<?= $i ?>)" style="width:36px;height:36px;border-radius:10px;border:none;font-weight:700;font-size:13px;cursor:pointer;background:<?= $i==$current_page_prod?'var(--tg-btn)':'var(--tg-secondary-bg)' ?>;color:<?= $i==$current_page_prod?'var(--tg-btn-text)':'var(--tg-text)' ?>;"><?= $i ?></button>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== PRODUCT DETAIL PAGE ===== -->
<div class="page <?= $page==='product'?'active':'' ?>" id="page-product">
    <?php if ($product_detail): 
        $pimg = get_image_url($product_detail['image_path'] ?? $product_detail['file_id'] ?? '');
        $price = $product_detail['has_discount'] && $product_detail['discount_price']>0 ? $product_detail['discount_price'] : $product_detail['base_price'];
    ?>
    <div style="position:relative;">
        <img src="<?= $pimg ?>" id="prod-main-img" style="width:100%;aspect-ratio:4/3;object-fit:cover;">
        <button onclick="navToPage('home')" style="position:absolute;top:16px;left:16px;width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.9);backdrop-filter:blur(8px);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <?php if ($product_detail['has_discount']): ?>
        <span style="position:absolute;top:16px;right:16px;background:#ef4444;color:#fff;border-radius:10px;padding:4px 12px;font-size:13px;font-weight:900;">-<?= $product_detail['discount_percent'] ?>%</span>
        <?php endif; ?>
    </div>
    <div style="padding:20px 16px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;"><?= htmlspecialchars($product_detail['cat_name'] ?? '') ?></p>
        <h2 id="prod-name" style="font-weight:900;font-size:20px;margin:4px 0 8px;"><?= htmlspecialchars($product_detail['name']) ?></h2>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <span id="prod-price" style="font-weight:900;font-size:22px;color:var(--tg-btn);"><?= number_format($price, 0, ',', ' ') ?> so'm</span>
            <?php if ($product_detail['has_discount']): ?>
            <span style="font-size:14px;color:#94a3b8;text-decoration:line-through;"><?= number_format($product_detail['base_price'], 0, ',', ' ') ?></span>
            <?php endif; ?>
        </div>
        <?php if ($product_detail['description']): ?>
        <p style="font-size:14px;color:#64748b;line-height:1.6;margin-bottom:16px;"><?= nl2br(htmlspecialchars($product_detail['description'])) ?></p>
        <?php endif; ?>

        <?php if ($product_detail['variants']): ?>
        <p style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Varianti tanlang</p>
        <div id="variants-wrap" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
            <?php foreach ($product_detail['variants'] as $v): ?>
            <button onclick="selectVariant(<?= $v['id'] ?>, <?= $v['price'] ?>, '<?= addslashes($v['image_path'] ?? '') ?>')" 
                    id="var-btn-<?= $v['id'] ?>"
                    style="padding:8px 16px;border-radius:12px;border:2px solid #e2e8f0;background:transparent;font-family:'Outfit',sans-serif;font-weight:700;font-size:13px;cursor:pointer;color:var(--tg-text);transition:all .2s;">
                <?= htmlspecialchars($v['variant_name']) ?>
                <small style="display:block;font-size:10px;color:#94a3b8;"><?= number_format($v['price'],0,',',' ') ?> so'm</small>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:12px;background:var(--tg-secondary-bg);border-radius:14px;padding:8px 16px;">
                <button onclick="changeQty(-1)" style="width:28px;height:28px;border-radius:8px;border:none;background:var(--tg-bg);font-size:18px;cursor:pointer;font-weight:900;display:flex;align-items:center;justify-content:center;">−</button>
                <span id="qty-display" style="font-weight:900;font-size:16px;min-width:24px;text-align:center;">1</span>
                <button onclick="changeQty(1)" style="width:28px;height:28px;border-radius:8px;border:none;background:var(--tg-bg);font-size:18px;cursor:pointer;font-weight:900;display:flex;align-items:center;justify-content:center;">+</button>
            </div>
            <button onclick="addToCartCurrent()" class="btn-tg" style="flex:1;">🛒 Savatga qo'shish</button>
        </div>
    </div>
    <script>
    var currentProdId = <?= $product_detail['id'] ?>;
    var currentVariantId = null;
    var currentQty = 1;
    var hasVariants = <?= count($product_detail['variants']) > 0 ? 'true' : 'false' ?>;
    function selectVariant(vid, vprice, vimg) {
        currentVariantId = vid;
        document.querySelectorAll('[id^="var-btn-"]').forEach(b => {
            b.style.border = '2px solid #e2e8f0';
            b.style.background = 'transparent';
        });
        var btn = document.getElementById('var-btn-' + vid);
        if (btn) { btn.style.border = '2px solid var(--tg-btn)'; btn.style.background = 'rgba(0,0,0,.05)'; }
        document.getElementById('prod-price').textContent = new Intl.NumberFormat('uz-UZ').format(vprice) + " so'm";
        if (vimg) document.getElementById('prod-main-img').src = vimg;
    }
    function changeQty(d) {
        currentQty = Math.max(1, currentQty + d);
        document.getElementById('qty-display').textContent = currentQty;
    }
    function addToCartCurrent() {
        if (hasVariants && !currentVariantId) {
            alert('Iltimos variant tanlang!'); return;
        }
        addToCart(currentProdId, currentVariantId, currentQty);
    }
    </script>
    <?php else: ?>
    <div style="padding:40px;text-align:center;color:#94a3b8;">Mahsulot topilmadi</div>
    <?php endif; ?>
</div>

<!-- ===== CART PAGE ===== -->
<div class="page <?= $page==='cart'?'active':'' ?>" id="page-cart">
    <div style="padding:20px 16px 8px;display:flex;align-items:center;justify-content:space-between;">
        <h2 style="font-weight:900;font-size:20px;">🛒 Savat</h2>
        <button onclick="clearCart()" style="font-size:12px;font-weight:700;color:#ef4444;background:none;border:none;cursor:pointer;">Tozalash</button>
    </div>
    <div id="cart-items" style="padding:0 16px;"></div>
    <div id="cart-total-wrap" style="padding:16px;"></div>
</div>

<!-- ===== CHECKOUT PAGE ===== -->
<div class="page <?= $page==='checkout'?'active':'' ?>" id="page-checkout">
    <div style="padding:20px 16px 8px;">
        <button onclick="navToPage('cart')" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:'Outfit',sans-serif;font-weight:700;font-size:13px;color:#94a3b8;margin-bottom:12px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            Orqaga
        </button>
        <h2 style="font-weight:900;font-size:20px;">📦 Buyurtma berish</h2>
    </div>
    <form id="checkout-form" style="padding:0 16px;display:flex;flex-direction:column;gap:12px;">
        <input type="text" name="full_name" placeholder="To'liq ismingiz *" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
        <input type="tel" name="phone" placeholder="Telefon raqam (+998...) *" required>
        <textarea name="address" placeholder="Yetkazib berish manzili *" rows="3" required style="resize:none;"></textarea>
        <textarea name="note" placeholder="Qo'shimcha izoh (ixtiyoriy)" rows="2" style="resize:none;"></textarea>
        <div id="checkout-summary" style="background:var(--tg-secondary-bg);border-radius:16px;padding:16px;"></div>
        <button type="submit" class="btn-tg" style="margin-top:4px;">✅ Buyurtma berish</button>
    </form>
</div>

<!-- ===== ORDERS PAGE ===== -->
<div class="page <?= $page==='orders'||$page==='profile'?'active':'' ?>" id="page-orders">
    <div style="padding:20px 16px 8px;display:flex;align-items:center;justify-content:space-between;">
        <h2 style="font-weight:900;font-size:20px;">📦 Buyurtmalarim</h2>
    </div>
    <?php if (!$orders_list): ?>
    <div style="text-align:center;padding:60px 24px;color:#94a3b8;">
        <div style="font-size:48px;">📭</div>
        <p style="font-weight:700;margin-top:12px;">Buyurtmalar yo'q</p>
        <button onclick="navToPage('home')" class="btn-tg" style="margin-top:16px;width:auto;padding:12px 24px;">🛍 Xarid qilish</button>
    </div>
    <?php else: ?>
    <div style="padding:0 16px;display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($orders_list as $ord): 
            $s_map = ['pending'=>['KUTILMOQDA','status-pending'],'accepted'=>['TASDIQLANDI','status-accepted'],'rejected'=>['RAD ETILDI','status-rejected'],'cancelled'=>['BEKOR','status-cancelled'],'delivered'=>['YETKAZILDI','status-delivered']];
            $s = $s_map[$ord['status']] ?? [$ord['status'], ''];
        ?>
        <div style="background:var(--tg-secondary-bg);border-radius:20px;padding:16px;cursor:pointer;" onclick="toggleOrder(<?= $ord['id'] ?>)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <span style="font-size:11px;font-weight:800;color:#94a3b8;">#<?= $ord['id'] ?> · <?= date('d.m.Y H:i', strtotime($ord['created_at'])) ?></span>
                <span class="<?= $s[1] ?>" style="font-size:10px;font-weight:800;padding:4px 10px;border-radius:8px;text-transform:uppercase;"><?= $s[0] ?></span>
            </div>
            <div style="font-weight:900;font-size:18px;"><?= number_format($ord['total_price'], 0, ',', ' ') ?> <small style="font-size:12px;font-weight:500;color:#94a3b8;">so'm</small></div>
            <div id="order-detail-<?= $ord['id'] ?>" style="display:none;margin-top:12px;border-top:1px solid rgba(0,0,0,.06);padding-top:12px;">
                <?php foreach ($ord['items'] as $it): 
                    $iimg = get_image_url($it['v_img'] ?? $it['p_img'] ?? '');
                ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <img src="<?= $iimg ?>" style="width:40px;height:40px;border-radius:10px;object-fit:cover;" onerror="this.style.display='none'">
                    <div>
                        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($it['pname']) ?></div>
                        <?php if ($it['variant_name']): ?><div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($it['variant_name']) ?></div><?php endif; ?>
                        <div style="font-size:11px;color:#64748b;"><?= number_format($it['price'],0,',',' ') ?> × <?= $it['quantity'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="font-size:12px;color:#64748b;background:rgba(0,0,0,.04);border-radius:10px;padding:10px;margin-top:6px;">
                    📍 <?= htmlspecialchars($ord['address']) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Pagination -->
    <?php if ($orders_total_pages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;padding:16px 16px 0;">
        <?php for ($i=1; $i<=$orders_total_pages; $i++): ?>
        <a href="webapp.php?page=orders&op=<?= $i ?>" style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;text-decoration:none;background:<?= $i==$orders_current_page?'var(--tg-btn)':'var(--tg-secondary-bg)' ?>;color:<?= $i==$orders_current_page?'var(--tg-btn-text)':'var(--tg-text)' ?>;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ===== PROFILE PAGE ===== -->
<div class="page" id="page-profile">
    <div style="padding:20px 16px;">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px;">
            <div style="width:56px;height:56px;border-radius:18px;background:var(--tg-btn);color:var(--tg-btn-text);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:22px;">
                <?= mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
            <div>
                <p style="font-weight:900;font-size:18px;"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                <?php if (isset($_SESSION['telegram_id'])): ?>
                <p style="font-size:12px;color:#94a3b8;">ID: <?= $_SESSION['telegram_id'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <button onclick="navToPage('orders')" style="display:flex;align-items:center;justify-content:space-between;background:var(--tg-secondary-bg);border:none;border-radius:16px;padding:16px;cursor:pointer;font-family:'Outfit',sans-serif;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:20px;">📦</span>
                    <span style="font-weight:700;font-size:14px;">Buyurtmalarim</span>
                </div>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>
            <a href="api/logout.php" style="display:flex;align-items:center;gap:10px;background:#fee2e2;border-radius:16px;padding:16px;text-decoration:none;color:#dc2626;">
                <span style="font-size:20px;">🚪</span>
                <span style="font-weight:700;font-size:14px;">Chiqish</span>
            </a>
        </div>
    </div>
</div>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
    <div class="nav-item active" id="nav-home" onclick="navToPage('home')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Bosh
    </div>
    <div class="nav-item" id="nav-catalog" onclick="navToPage('catalog')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        Katalog
    </div>
    <div class="nav-item" id="nav-cart" onclick="navToPage('cart')" style="position:relative;">
        <div style="position:relative;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span id="cart-nav-badge" style="display:none;position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:900;align-items:center;justify-content:center;border:2px solid var(--tg-bg);"></span>
        </div>
        Savat
    </div>
    <div class="nav-item" id="nav-orders" onclick="navToPage('orders')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Buyurtma
    </div>
    <div class="nav-item" id="nav-profile" onclick="navToPage('profile')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Profil
    </div>
</nav>

</div><!-- #app -->
<?php endif; ?>
<?php endif; ?>

<script>
// ===== TELEGRAM WEB APP INIT =====
const tg = window.Telegram?.WebApp;
if (tg) { tg.ready(); tg.expand(); }

// ===== CART (localStorage) =====
let cart = JSON.parse(localStorage.getItem('wa_cart') || '[]');

function saveCart() {
    localStorage.setItem('wa_cart', JSON.stringify(cart));
    updateCartBadge();
}

function updateCartBadge() {
    const total = cart.reduce((s, i) => s + i.qty, 0);
    const badge = document.getElementById('cart-nav-badge');
    if (badge) {
        badge.textContent = total;
        badge.style.display = total > 0 ? 'flex' : 'none';
    }
}

function addToCart(prodId, variantId, qty) {
    const existing = cart.find(i => i.prodId === prodId && i.variantId === variantId);
    if (existing) existing.qty += qty;
    else cart.push({prodId, variantId, qty});
    saveCart();
    if (tg) tg.HapticFeedback?.impactOccurred('medium');
    showToast('✅ Savatga qo\'shildi!');
    setTimeout(() => navToPage('cart'), 500);
}

function renderCart() {
    const el = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total-wrap');
    if (!el || !totalEl) return;
    if (!cart.length) {
        el.innerHTML = '<div style="text-align:center;padding:60px 24px;color:#94a3b8;"><div style="font-size:48px;">🛒</div><p style="font-weight:700;margin-top:12px;">Savat bo\'sh</p><button onclick="navToPage(\'home\')" style="margin-top:16px;padding:12px 24px;background:var(--tg-btn);color:var(--tg-btn-text);border:none;border-radius:14px;font-family:\'Outfit\',sans-serif;font-weight:800;font-size:13px;cursor:pointer;">🛍 Xarid qilish</button></div>';
        totalEl.innerHTML = '';
        return;
    }
    // Simpler rendering - just show cart items from localStorage
    let html = '';
    cart.forEach((item, idx) => {
        html += `<div style="display:flex;align-items:center;justify-content:space-between;background:var(--tg-secondary-bg);border-radius:16px;padding:14px;margin-bottom:10px;">
            <div>
                <p style="font-weight:700;font-size:13px;">Mahsulot #${item.prodId}</p>
                ${item.variantId ? `<p style="font-size:11px;color:#94a3b8;">Variant #${item.variantId}</p>` : ''}
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="display:flex;align-items:center;gap:8px;background:var(--tg-bg);border-radius:10px;padding:4px 10px;">
                    <button onclick="changeCartQty(${idx},-1)" style="border:none;background:none;cursor:pointer;font-size:16px;font-weight:900;color:var(--tg-text);">−</button>
                    <span style="font-weight:800;font-size:14px;">${item.qty}</span>
                    <button onclick="changeCartQty(${idx},1)" style="border:none;background:none;cursor:pointer;font-size:16px;font-weight:900;color:var(--tg-text);">+</button>
                </div>
                <button onclick="removeFromCart(${idx})" style="border:none;background:#fee2e2;color:#dc2626;border-radius:10px;width:32px;height:32px;cursor:pointer;font-size:16px;">✕</button>
            </div>
        </div>`;
    });
    el.innerHTML = html;
    totalEl.innerHTML = `<button onclick="navToPage('checkout')" class="btn-tg">📦 Buyurtma berish (${cart.length} ta mahsulot)</button>`;
}

function changeCartQty(idx, d) {
    cart[idx].qty = Math.max(1, cart[idx].qty + d);
    saveCart();
    renderCart();
}

function removeFromCart(idx) {
    cart.splice(idx, 1);
    saveCart();
    renderCart();
}

function clearCart() {
    if (confirm('Savatni tozalaysizmi?')) { cart = []; saveCart(); renderCart(); }
}

// ===== CHECKOUT =====
document.getElementById('checkout-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('csrf_token', '<?= generate_csrf_token() ?>');
    fd.append('cart_data', JSON.stringify(cart));
    
    // Build cart in session via AJAX first
    const cartRes = await fetch('api/webapp_set_cart.php', {method:'POST', body: JSON.stringify(cart), headers:{'Content-Type':'application/json'}});
    
    const res = await fetch('api/place_order.php', {method:'POST', body: fd});
    if (res.ok) {
        cart = [];
        saveCart();
        showToast('✅ Buyurtma qabul qilindi!');
        setTimeout(() => { navToPage('orders'); location.reload(); }, 1000);
    }
});

// ===== NAVIGATION =====
function navToPage(page) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    
    const pageEl = document.getElementById('page-' + page);
    if (pageEl) pageEl.classList.add('active');
    
    const navEl = document.getElementById('nav-' + page);
    if (navEl) navEl.classList.add('active');
    
    if (page === 'cart') renderCart();
    
    window.scrollTo({top:0, behavior:'smooth'});
}

function openProduct(id) {
    window.location.href = 'webapp.php?page=product&id=' + id;
}

function loadPage(p) {
    const url = new URL(window.location);
    url.searchParams.set('p', p);
    window.location.href = url;
}

function filterCat(cid) {
    const url = new URL(window.location);
    url.searchParams.set('cat', cid);
    url.searchParams.delete('p');
    window.location.href = url;
}

let searchTimer;
function debounceSearch(q) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const url = new URL(window.location);
        url.searchParams.set('q', q);
        url.searchParams.delete('p');
        window.location.href = url;
    }, 600);
}

function toggleOrder(id) {
    const el = document.getElementById('order-detail-'+id);
    if (el) el.style.display = el.style.display==='none' ? 'block' : 'none';
}

function showToast(msg) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:var(--tg-btn);color:var(--tg-btn-text);padding:12px 20px;border-radius:20px;font-family:Outfit,sans-serif;font-weight:700;font-size:13px;z-index:9999;white-space:nowrap;';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
}

// Init
updateCartBadge();
<?php if ($page === 'home'): ?>
navToPage('home');
<?php elseif ($page === 'cart'): ?>
navToPage('cart');
<?php elseif ($page === 'orders' || $page === 'profile'): ?>
navToPage('orders');
<?php elseif ($page === 'product'): ?>
// product page is shown via PHP
<?php endif; ?>
</script>
</body>
</html>
