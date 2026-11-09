# Al Kawkaba — Luxury Abaya Store

Bilingual (Arabic RTL / English LTR) e-commerce store with a full admin dashboard.
Plain PHP 8.1+ (no framework, no Composer) · MySQL / MariaDB · local media storage. Runs on ordinary shared hosting.

## Run locally

Requirements: PHP 8.1+ with `pdo_mysql`, `gd` (WebP), `intl`, `fileinfo`, `mbstring`; MySQL 5.7+ or MariaDB 10.3+.

```bash
cp config.example.php config.php        # set db credentials + a random 'secret' (32+ chars)
mysql -u root -e "CREATE DATABASE alkawkaba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php database/seed.php                    # creates tables + demo catalog, homepage and admin user
php -S localhost:8000 -t public public/index.php
```

Open http://localhost:8000 · admin: http://localhost:8000/admin (`admin_email` / `admin_password` from `config.php`). **Change before going live.**
The seed imports the photos and videos in `public/brand/` into the media library and builds the catalog and homepage from them.
Product names, prices and texts are starting points: edit them in **Admin → Products**.
Note: `php database/seed.php` wipes products, collections, categories, sections and media (not orders/users/settings).

## Deploy to shared hosting (cPanel, Hostinger, …)

1. Create a MySQL database + user in the hosting panel, then import `database/schema.sql` with phpMyAdmin.
2. Upload the whole project. Point the domain's document root at `public/`.
   If you can't change the document root, upload into it as-is: the root `.htaccess` forwards requests to `public/` and blocks `app/`, `database/`, `uploads/` and `config.php`.
3. Copy `config.example.php` to **`alkawkaba-config.php` in the folder above the project** (on Hostinger: `domains/<your-domain>/`, next to `public_html`) and fill in the database, `secret`, `site_url`, and `'debug' => false`.
   Outside the project folder, deployments can never delete it, and uploads go to `uploads/` beside it, so they survive deployments too. (`config.php` inside the project also works, but a deploy that resets the folder deletes it.)
4. Visit `/admin/login`. With no staff accounts yet, it asks you to **create the first admin account** (no SSH needed).
5. Make sure `uploads/` is writable by PHP. `public/.user.ini` / `.htaccess` raise upload limits to 200MB for videos; some hosts cap this in their panel.

## Changing styles

Pages use Tailwind classes. The compiled stylesheet `public/assets/app.css` is committed, so the server needs no Node.
After adding or changing classes in `app/views/**` or `public/assets/*.js`, rebuild it:

```bash
npm install        # once
npm run css        # or: npm run css:watch
```

Brand colours, fonts and shared styles (`.btn`, `.eyebrow`, `.display`…) live in `resources/css/app.css`.

## Project layout

| Path | What's there |
|---|---|
| `public/index.php` | Front controller: routes every request (`app/routes.php`) |
| `public/assets/store.js` | Storefront behaviour: bag, wishlist, quick view, gallery, hero slideshow, checkout |
| `public/assets/admin.js` | Admin widgets: uploads, media picker, gallery manager, section editor, reordering |
| `app/controllers/` | `store.php` (storefront pages & actions), `admin.php` (dashboard) |
| `app/views/` | PHP templates; homepage blocks in `views/sections.php` |
| `app/lib/` | DB, auth, i18n (all storefront text), settings, catalog, orders, payments, storage |
| `database/` | `schema.sql`, `seed.php` |

## Admin dashboard (`/admin`)

| Area | What you can do |
|---|---|
| Products | AR/EN name, description, fabric, details, care, delivery; price, discount price, SKU, stock, availability; sizes, colours; New / Featured / Limited / Ready flags; SEO; drag-and-drop images & videos, reorder, primary image, alt text, video thumbnails |
| Collections | Create, desktop + mobile covers (image or video), assign products, order, publish/hide |
| Homepage | Add / remove / reorder / hide sections: hero slideshow, product carousel, collections, shop-by-occasion, ready-to-wear, atelier story, editorial campaign, values, text. Each has its own media, texts, CTA and product/collection picks |
| Media library | Bulk upload, alt text, delete. JPG/PNG auto-converted to WebP, EXIF stripped; resized copies are generated on demand and cached in `uploads/.cache` |
| Orders | Filter/search, details, order/payment/shipping status, tracking number, history log. Cancelling restocks |
| Customers, Discounts | Customer + guest buyer lists with order history; % or fixed codes with min order, max uses, expiry |
| Settings (admin only) | Logo, brand text, contact, social, currency, shipping rate/free threshold/countries, payment methods, bank details, policies, SEO |
| Staff (admin only) | Add Admins (everything) or Editors (everything except settings & staff) |

## Production notes

- **Security:** logins use an HMAC-signed cookie (`secret` in `config.php`); every form and AJAX call is CSRF-protected; all queries are prepared statements.
- **Media:** stored in `upload_dir` (default `./uploads`). Keep it on a persistent disk and include it in backups. For S3/R2, replace `save_upload()` / `delete_media()` / `serve_media()` in `app/lib/storage.php`.
- **Payments:** Cash on delivery + bank transfer work now. Card gateway: pick it in Settings → Payments and implement `create_checkout()` in `app/lib/payments.php` (plus a webhook that marks the order paid).
- Set `site_url` in `config.php` for correct social/SEO URLs.
