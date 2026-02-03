# 🛍️ E-STORE - Modern E-commerce Platform

> Zamonaviy, tez va xavfsiz onlayn do'kon platformasi Telegram bot integratsiyasi bilan

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![SQLite](https://img.shields.io/badge/Database-SQLite-green.svg)](https://sqlite.org)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## 📋 Mundarija

- [Xususiyatlar](#-xususiyatlar)
- [Texnologiyalar](#-texnologiyalar)
- [O'rnatish](#-ornatish)
- [Konfiguratsiya](#-konfiguratsiya)
- [Foydalanish](#-foydalanish)
- [Telegram Bot](#-telegram-bot)
- [Deployment](#-deployment)
- [Changelog](#-changelog)

## ✨ Xususiyatlar

### 🎨 Frontend
- **Modern UI/UX** - Qora-oq minimalist dizayn
- **Responsive Design** - Barcha qurilmalarda mukammal ishlaydi
- **Alpine.js** - Reaktiv komponentlar
- **TailwindCSS** - Utility-first CSS framework
- **Smooth Animations** - Animate.css integratsiyasi
- **HTMX** - Dynamic content loading (optional)

### 🔐 Autentifikatsiya
- **Telefon raqam bilan login** - +998 prefiksi majburiy
- **Xavfsiz parol hash** - `password_hash()` / `password_verify()`
- **Session management** - Xavfsiz sessiya boshqaruvi
- **Auto-login** - Ro'yxatdan o'tgandan keyin avtomatik kirish

### 🛒 E-commerce
- **Mahsulot katalogi** - Kategoriyalar va variantlar
- **Savatcha** - Real-time yangilanish
- **Qidiruv** - Tez va aniq qidiruv tizimi
- **Buyurtmalar** - To'liq buyurtma boshqaruvi
- **Chegirmalar** - Foiz asosida chegirma tizimi

### 🤖 Telegram Bot
- **Admin panel** - To'liq CRUD operatsiyalari
- **Mahsulot boshqaruvi** - Rasm, narx, tavsif
- **Kategoriya boshqaruvi** - Kategoriyalarni qo'shish/o'chirish
- **Poster boshqaruvi** - Bosh sahifa bannerlar
- **Buyurtma bildirnomalari** - Real-time xabarlar

### 📊 Boshqa
- **SQLite Database** - Tez va engil
- **File caching** - Telegram rasmlar keshi
- **Error handling** - To'liq xatolik boshqaruvi
- **CSRF Protection** - Cross-site request forgery himoyasi

## 🛠 Texnologiyalar

### Backend
```
PHP 8.0+
SQLite 3
PDO (PHP Data Objects)
```

### Frontend
```
TailwindCSS 3.x
Alpine.js 3.x
HTMX 1.9.x
Animate.css 4.x
Google Fonts (Outfit)
```

### Telegram
```
Telegram Bot API
cURL for HTTP requests
Webhook integration
```

## 📦 O'rnatish

### 1. Loyihani klonlash

```bash
git clone https://github.com/devloper-dilshod/e-store.git
cd store
```

### 2. Konfiguratsiya

`core/config.php` faylida sozlamalarni o'zgartiring:

```php
// Telegram Bot Token
$telegram_bot_token = 'YOUR_BOT_TOKEN_HERE';

// Admin Chat ID
$admin_chat_id = 'YOUR_CHAT_ID_HERE';
```

### 3. Ruxsatlar

```bash
chmod -R 755 protected/
chmod -R 777 protected/data/
chmod -R 777 protected/cache/
```

### 4. Web server sozlamalari

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## ⚙️ Konfiguratsiya

### Database

Database avtomatik yaratiladi. Qo'lda yaratish kerak emas.

Joylashuv: `protected/data/database.sqlite`

### Telegram Bot

1. **Bot yaratish:**
   - [@BotFather](https://t.me/BotFather) ga `/newbot` yuboring
   - Bot nomini kiriting
   - Tokenni saqlang

2. **Webhook o'rnatish:**
   ```bash
   php bot/set_webhook.php
   ```

3. **Admin ID olish:**
   - [@userinfobot](https://t.me/userinfobot) ga `/start` yuboring
   - Chat ID ni `core/config.php` ga kiriting

### Xavfsizlik

- `protected/` papkasi tashqaridan kirish mumkin emas
- Session cookie `httponly` rejimida
- CSRF tokenlar barcha formalarda
- SQL injection himoyasi (PDO prepared statements)
- XSS himoyasi (`htmlspecialchars`)

## 🚀 Foydalanish

### Foydalanuvchi interfeysi

1. **Ro'yxatdan o'tish:**
   - `/register.php` ga o'ting
   - Ism, telefon (+998XXXXXXXXX), parol kiriting
   - Avtomatik login

2. **Kirish:**
   - `/login.php` ga o'ting
   - Telefon va parol kiriting

3. **Xarid qilish:**
   - Mahsulotlarni ko'ring
   - Savatga qo'shing
   - Checkout qiling

### Admin panel (Telegram Bot)

1. **Kategoriya qo'shish:**
   ```
   /start → Kategoriyalar → Qo'shish
   ```

2. **Mahsulot qo'shish:**
   ```
   /start → Mahsulotlar → Qo'shish
   ```

3. **Poster qo'shish:**
   ```
   /start → Posterlar → Qo'shish
   ```

## 🤖 Telegram Bot

### Komandalar

- `/start` - Botni ishga tushirish
- Admin panel - Faqat admin uchun

### Bot strukturasi

```
bot/
├── filmchibot.php      # Webhook handler
├── set_webhook.php     # Webhook o'rnatish
└── bot_state table     # State management
```

### Webhook URL

```
https://yourdomain.com/store/bot/filmchibot.php
```

## 🌐 Deployment

### Shared Hosting (cPanel)

1. **Fayllarni yuklash:**
   ```
   FileZilla/FTP orqali barcha fayllarni yuklang
   ```

2. **Database ruxsatlari:**
   ```bash
   chmod 777 protected/data/
   ```

3. **Webhook o'rnatish:**
   ```
   https://yourdomain.com/store/bot/set_webhook.php
   ```

### VPS/Dedicated Server

1. **Apache/Nginx sozlash**
2. **PHP 8.0+ o'rnatish**
3. **SQLite extension yoqish**
4. **SSL sertifikat (Let's Encrypt)**

### Git Deployment

```bash
# Serverda
cd /var/www/html/store
git pull origin main
chmod -R 777 protected/data/
chmod -R 777 protected/cache/
```

## 📝 Changelog

### v2.0.0 (2026-02-03)

#### ✨ Yangi xususiyatlar
- **Telefon raqam autentifikatsiyasi** - Username o'rniga telefon raqam
- **+998 majburiy prefiks** - O'zbekiston telefon raqamlari
- **Standalone view lar** - HTMX siz mustaqil sahifalar
- **Yaxshilangan parol ko'rsatish** - Yangilangan UI/UX
- **Xato xabarlari** - Batafsil validatsiya xabarlari

#### 🔧 Tuzatishlar
- Form action yo'llari to'g'irlandi (`api/login.php` → `login.php`)
- HTMX dependency muammolari hal qilindi
- Session boshqaruvi yaxshilandi
- Database query optimizatsiyasi

#### 🗑️ O'chirilgan
- `api/login.php` - Endi ishlatilmaydi
- `api/register.php` - Endi ishlatilmaydi
- HTMX dependency login/register da

### v1.0.0 (2026-01-XX)

- Dastlabki release
- Asosiy e-commerce funksiyalari
- Telegram bot integratsiyasi
- HTMX dynamic loading

## 📂 Fayl strukturasi

```
store/
├── bot/                          # Telegram bot
│   ├── filmchibot.php           # Webhook handler
│   └── set_webhook.php          # Webhook setup
├── components/                   # UI komponentlar
│   ├── header.php               # Header + navigation
│   └── footer.php               # Footer
├── core/                        # Core funksiyalar
│   ├── config.php               # Konfiguratsiya + DB
│   └── render.php               # View rendering
├── protected/                   # Himoyalangan papka
│   ├── data/                    # SQLite database
│   └── cache/                   # Telegram rasm keshi
├── views/                       # View fayllar
│   ├── home_view.php            # Bosh sahifa
│   ├── login_view_standalone.php    # Login (standalone)
│   ├── register_view_standalone.php # Register (standalone)
│   ├── product_view.php         # Mahsulot sahifasi
│   ├── cart_view.php            # Savatcha
│   └── ...
├── index.php                    # Bosh sahifa
├── login.php                    # Login controller
├── register.php                 # Register controller
├── product.php                  # Mahsulot controller
├── cart.php                     # Savatcha controller
├── checkout.php                 # Checkout controller
├── search.php                   # Qidiruv controller
├── image.php                    # Rasm proxy
└── README.md                    # Bu fayl
```

## 🔒 Xavfsizlik

### Best Practices

1. **Parollar:**
   - `password_hash()` bilan hash qilinadi
   - `PASSWORD_DEFAULT` algoritm (bcrypt)
   - Salt avtomatik qo'shiladi

2. **SQL Injection:**
   - PDO prepared statements
   - Hech qachon to'g'ridan-to'g'ri query yo'q

3. **XSS:**
   - `htmlspecialchars()` barcha outputda
   - `ENT_QUOTES` flag

4. **CSRF:**
   - Token barcha formalarda
   - Session-based validation

5. **Session:**
   - `httponly` cookie
   - `use_only_cookies` yoqilgan
   - Xavfsiz session ID

## 🤝 Hissa qo'shish

Pull requestlar xush kelibsiz! Katta o'zgarishlar uchun avval issue oching.

## 📄 License

[MIT](LICENSE)

## 👨‍💻 Muallif

**Your Name**
- Telegram: [@yourusername](https://t.me/yourusername)
- Email: your.email@example.com

## 🙏 Minnatdorchilik

- [TailwindCSS](https://tailwindcss.com)
- [Alpine.js](https://alpinejs.dev)
- [HTMX](https://htmx.org)
- [Telegram Bot API](https://core.telegram.org/bots/api)

---

<div align="center">
  <strong>E-STORE</strong> bilan savdo qiling! 🚀
</div>
