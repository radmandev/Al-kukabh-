# Al Kawkaba — Luxury Abaya Store

Bilingual (Arabic RTL / English LTR) e-commerce store with a full admin dashboard.
Next.js 15 (App Router) · Prisma · SQLite (dev) / Postgres (prod) · local media storage.

## Run locally

```bash
npm install
cp .env.example .env      # then set AUTH_SECRET to a long random string
npm run db:push           # create database
npm run seed              # demo catalog + homepage + admin user
npm run dev               # http://localhost:3000  ·  admin: /admin
```

Default admin: `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`. **Change before going live.**
Demo images are brand-toned placeholders. Replace them in **Admin → Media / Products / Homepage**.
Note: `npm run seed` wipes products, collections, sections and media (not orders/users).

## Admin dashboard (`/admin`)

| Area | What you can do |
|---|---|
| Products | AR/EN name, description, fabric, details, care, delivery; price, discount price, SKU, stock, availability; sizes, colours; New / Featured / Limited / Ready flags; SEO; drag-and-drop images & videos, reorder, primary image, alt text, video thumbnails |
| Collections | Create, desktop + mobile covers (image or video), assign products, order, publish/hide |
| Homepage | Add / remove / reorder / hide sections: hero slideshow, product carousel, collections, shop-by-occasion, ready-to-wear, atelier story, editorial campaign, values, text. Each has its own media, texts, CTA and product/collection picks |
| Media library | Bulk upload, alt text, delete. JPG/PNG auto-converted to WebP, EXIF stripped |
| Orders | Filter/search, details, order/payment/shipping status, tracking number, history log. Cancelling restocks |
| Customers, Discounts | Customer + guest buyer lists with order history; % or fixed codes with min order, max uses, expiry |
| Settings (admin only) | Logo, brand text, contact, social, currency, shipping rate/free threshold/countries, payment methods, bank details, policies, SEO |
| Staff (admin only) | Add Admins (everything) or Editors (everything except settings & staff) |

## Production notes

- **Database:** set `provider = "postgresql"` in `prisma/schema.prisma` and point `DATABASE_URL` to Postgres.
- **Media:** stored in `UPLOAD_DIR` (default `./uploads`). Use a persistent disk/volume. For S3/R2, replace `saveUpload`/`deleteMedia` in `src/lib/storage.ts`.
- **Payments:** Cash on delivery + bank transfer work now. Card gateway: pick it in Settings → Payments and implement `createCheckout()` in `src/lib/payments.ts` (plus a webhook that marks the order paid).
- Set `SITE_URL` for correct social/SEO URLs.
