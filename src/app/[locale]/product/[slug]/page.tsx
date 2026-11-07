import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { db } from "@/lib/db";
import { findProducts, posterMap, productInclude, shape, toCard } from "@/lib/catalog";
import { dict, L, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";
import { Gallery } from "@/components/store/Gallery";
import { AddToBag } from "@/components/store/AddToBag";
import { Price } from "@/components/store/ProductCard";
import { ProductGrid } from "@/components/store/ProductGrid";
import { RecentlyViewed, StickyBuy } from "@/components/store/ProductExtras";
import { Icon } from "@/components/Icon";

type P = { params: Promise<{ locale: Locale; slug: string }> };
async function get(slug: string) {
  const p = await db.product.findFirst({ where: { slug: decodeURIComponent(slug), published: true }, include: productInclude });
  return p && shape(p);
}

export async function generateMetadata({ params }: P): Promise<Metadata> {
  const { locale: l, slug } = await params;
  const p = await get(slug);
  if (!p) return {};
  const img = p.gallery.find((m) => m.kind === "image");
  return {
    title: p.seoTitle || L(p, "name", l),
    description: p.seoDesc || L(p, "desc", l).slice(0, 160),
    openGraph: { images: img ? [img.url] : [] },
    alternates: { canonical: `/${l}/product/${p.slug}`, languages: { ar: `/ar/product/${p.slug}`, en: `/en/product/${p.slug}` } },
  };
}

export default async function ProductPage({ params }: P) {
  const { locale: l, slug } = await params;
  const p = await get(slug);
  if (!p) notFound();
  const t = dict(l);
  const s = await getSettings();
  const posters = await posterMap(p.gallery.map((m) => m.posterId));
  const near = [p.collectionId && { collectionId: p.collectionId }, p.categoryId && { categoryId: p.categoryId }].filter(Boolean) as object[];
  const related = await findProducts({ id: { not: p.id }, ...(near.length ? { OR: near } : {}) }, { createdAt: "desc" }, 4);
  const name = L(p, "name", l);
  const card = toCard(p);
  const ld = {
    "@context": "https://schema.org", "@type": "Product", name, sku: p.sku, description: L(p, "desc", l),
    image: p.gallery.filter((m) => m.kind === "image").map((m) => m.url), brand: { "@type": "Brand", name: L(s.brand, "name", l) },
    offers: { "@type": "Offer", price: p.salePrice ?? p.price, priceCurrency: s.currency, availability: `https://schema.org/${p.availability === "out_of_stock" ? "OutOfStock" : p.availability === "made_to_order" ? "PreOrder" : "InStock"}` },
  };
  const acc: [string, string][] = ([[t.description, L(p, "desc", l)], [t.fabric, L(p, "fabric", l)], [t.details, L(p, "details", l)], [t.care, L(p, "care", l)], [t.delivery, L(p, "delivery", l)]] as [string, string][]).filter(([, v]) => v);

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(ld).replace(/</g, "\\u003c") }} />
      <div className="mx-auto grid max-w-[1600px] gap-8 md:grid-cols-[1.25fr_1fr] md:gap-12 md:px-10 md:py-10 lg:gap-20">
        <Gallery name={name} items={p.gallery.map((m) => ({ url: m.url, kind: m.kind, poster: m.posterId ? posters.get(m.posterId) : undefined, alt: L(m, "alt", l) || name }))} />
        <div className="px-4 md:px-0">
          <div className="md:sticky md:top-36">
            <nav className="eyebrow mb-6 text-stone" aria-label="Breadcrumb">
              <Link href={`/${l}/shop`} className="link-u">{t.shop}</Link>
              {p.collection && <> / <Link href={`/${l}/collections/${p.collection.slug}`} className="link-u">{L(p.collection, "name", l)}</Link></>}
            </nav>
            <div className="flex gap-2">
              {p.isNew && <span className="eyebrow border border-line px-2 py-1 text-[.58rem]">{t.new}</span>}
              {p.isLimited && <span className="eyebrow bg-ink px-2 py-1 text-[.58rem] text-gold-soft">{t.limitedLabel}</span>}
            </div>
            <h1 className="display mt-4 text-4xl md:text-5xl">{name}</h1>
            <Price p={p} l={l} currency={s.currency} className="mt-4 text-lg" />
            <p className="mt-3 flex items-center gap-2 text-sm text-stone">
              <Icon name="truck" className="h-4 w-4" />
              {p.availability === "out_of_stock" ? t.outOfStock : p.availability === "made_to_order" ? t.madeToOrder : t.inStock}
            </p>
            <div id="buy" className="mt-10"><AddToBag p={card} l={l} t={t} /></div>
            <div className="mt-10 divide-y divide-line border-y border-line">
              {acc.map(([k, v], i) => (
                <details key={k} open={i === 0} className="group py-5">
                  <summary className="eyebrow flex cursor-pointer list-none items-center justify-between">{k}<Icon name="plus" className="h-3.5 w-3.5 transition group-open:rotate-45" /></summary>
                  <p className="mt-4 whitespace-pre-line text-sm leading-7 text-stone">{v}</p>
                </details>
              ))}
            </div>
            <p className="mt-6 text-xs text-stone">SKU {p.sku}</p>
          </div>
        </div>
      </div>
      {related.length > 0 && (
        <section className="mx-auto max-w-[1600px] px-4 py-20 md:px-10">
          <h2 className="display mb-10 text-center text-3xl md:text-4xl">{t.related}</h2>
          <ProductGrid ps={related} l={l} t={t} currency={s.currency} />
        </section>
      )}
      <RecentlyViewed p={card} l={l} t={t} currency={s.currency} />
      <StickyBuy p={card} l={l} t={t} currency={s.currency} />
    </>
  );
}
