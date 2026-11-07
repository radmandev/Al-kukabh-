import type { Metadata } from "next";
import type { Prisma } from "@prisma/client";
import { db } from "@/lib/db";
import { findProducts } from "@/lib/catalog";
import { dict, L, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";
import { ProductGrid } from "@/components/store/ProductGrid";
import { AutoForm } from "@/components/store/AutoForm";

type SP = Promise<Record<string, string | undefined>>;
export async function generateMetadata({ params }: { params: Promise<{ locale: Locale }> }): Promise<Metadata> {
  return { title: dict((await params).locale).shop };
}

export default async function Shop({ params, searchParams }: { params: Promise<{ locale: Locale }>; searchParams: SP }) {
  const { locale: l } = await params;
  const q = await searchParams;
  const t = dict(l);
  const [s, cats] = await Promise.all([getSettings(), db.category.findMany({ orderBy: { sort: "asc" } })]);

  const where: Prisma.ProductWhereInput = {};
  if (q.q) where.OR = [{ nameAr: { contains: q.q } }, { nameEn: { contains: q.q } }, { sku: { contains: q.q } }, { descAr: { contains: q.q } }, { descEn: { contains: q.q } }];
  if (q.category) where.category = { slug: q.category };
  if (q.tag === "new") where.isNew = true;
  if (q.tag === "limited") where.isLimited = true;
  if (q.tag === "ready") where.isReady = true;
  if (q.tag === "featured") where.isFeatured = true;
  if (q.availability) where.availability = q.availability;
  if (q.size) where.sizes = { contains: `"${q.size}"` };
  const order: Prisma.ProductOrderByWithRelationInput = q.sort === "priceAsc" ? { price: "asc" } : q.sort === "priceDesc" ? { price: "desc" } : { createdAt: "desc" };
  const ps = await findProducts(where, order);
  const allSizes = [...new Set((await db.product.findMany({ where: { published: true }, select: { sizes: true } })).flatMap((p) => JSON.parse(p.sizes) as string[]))];

  const cat = cats.find((c) => c.slug === q.category);
  const title = q.q ? `“${q.q}”` : cat ? L(cat, "name", l) : q.tag === "new" ? t.newArrivals : q.tag === "limited" ? t.limited : q.tag === "ready" ? t.ready : t.shop;
  const sel = "border-0 border-b border-line bg-transparent py-2 pe-6 text-sm outline-none focus:border-ink";

  return (
    <div className="mx-auto max-w-[1600px] px-4 pb-24 md:px-10">
      <header className="py-14 text-center md:py-20">
        <p className="eyebrow text-gold">{t.shop}</p>
        <h1 className="display mt-3 text-4xl md:text-6xl">{title}</h1>
      </header>
      <AutoForm className="mb-10 flex flex-wrap items-end justify-between gap-4 border-b border-line pb-4">
        {q.q && <input type="hidden" name="q" value={q.q} />}
        {q.tag && <input type="hidden" name="tag" value={q.tag} />}
        <div className="flex flex-wrap gap-4">
          <label className="sr-only" htmlFor="f-cat">{t.category}</label>
          <select id="f-cat" name="category" defaultValue={q.category ?? ""} className={sel}>
            <option value="">{t.category}: {t.all}</option>
            {cats.map((c) => <option key={c.id} value={c.slug}>{L(c, "name", l)}</option>)}
          </select>
          {allSizes.length > 0 && (
            <select name="size" defaultValue={q.size ?? ""} className={sel} aria-label={t.size}>
              <option value="">{t.size}: {t.all}</option>
              {allSizes.map((x) => <option key={x}>{x}</option>)}
            </select>
          )}
          <select name="availability" defaultValue={q.availability ?? ""} className={sel} aria-label={t.availability}>
            <option value="">{t.availability}: {t.all}</option>
            <option value="in_stock">{t.inStock}</option>
            <option value="made_to_order">{t.madeToOrder}</option>
          </select>
        </div>
        <div className="flex items-center gap-4">
          <span className="text-xs text-stone">{ps.length} {t.results}</span>
          <select name="sort" defaultValue={q.sort ?? ""} className={sel} aria-label={t.sortBy}>
            <option value="">{t.newest}</option>
            <option value="priceAsc">{t.priceAsc}</option>
            <option value="priceDesc">{t.priceDesc}</option>
          </select>
        </div>
        <noscript><button className="btn">{t.filter}</button></noscript>
      </AutoForm>
      <ProductGrid ps={ps} l={l} t={t} currency={s.currency} />
    </div>
  );
}
