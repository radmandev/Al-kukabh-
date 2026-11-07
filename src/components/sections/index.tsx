import Link from "next/link";
import { db } from "@/lib/db";
import { findProducts, productsByIds, toCard } from "@/lib/catalog";
import { json } from "@/lib/format";
import { L, localize, type Locale, type T } from "@/lib/i18n";
import type { MediaRef } from "@/lib/sections";
import { Media } from "@/components/Media";
import { ProductCard } from "@/components/store/ProductCard";
import { Hero } from "./Hero";
import { Rail } from "./Rail";

type D = Record<string, unknown> & { link?: string };
type Ctx = { l: Locale; t: T; currency: string };
const m = (r: unknown, l: Locale) => {
  const x = r as MediaRef | undefined;
  return x?.url ? { url: x.url, kind: x.kind, poster: x.poster, alt: L(x, "alt", l) } : undefined;
};
const list = (d: D, k: string) => (Array.isArray(d[k]) ? (d[k] as D[]) : []);

function Head({ d, l, center = true, light }: { d: D; l: Locale; center?: boolean; light?: boolean }) {
  const eyebrow = L(d, "eyebrow", l), title = L(d, "title", l), text = L(d, "text", l);
  if (!eyebrow && !title && !text) return null;
  return (
    <div className={`${center ? "mx-auto text-center" : ""} max-w-2xl px-6 md:px-10`}>
      {eyebrow && <p className={`eyebrow ${light ? "text-gold-soft" : "text-gold"}`}>{eyebrow}</p>}
      {title && <h2 className="display mt-3 text-4xl md:text-5xl">{title}</h2>}
      {title && <Ornament center={center} />}
      {text && <p className={`mt-5 whitespace-pre-line text-sm leading-7 md:text-base md:leading-8 ${light ? "text-ivory/70" : "text-stone"}`}>{text}</p>}
    </div>
  );
}
/** gold rule + star: the "kawkaba" mark under every title */
function Ornament({ center = true }: { center?: boolean }) {
  return (
    <div aria-hidden className={`mt-5 flex items-center gap-3 text-gold ${center ? "justify-center" : ""}`}>
      <span className="h-px w-14 bg-gradient-to-l from-gold/70 to-transparent" />
      <svg viewBox="0 0 24 24" className="h-3 w-3" fill="currentColor"><path d="M12 0l2.6 9.4L24 12l-9.4 2.6L12 24l-2.6-9.4L0 12l9.4-2.6z" /></svg>
      <span className="h-px w-14 bg-gradient-to-r from-gold/70 to-transparent" />
    </div>
  );
}
function Cta({ d, l, light }: { d: D; l: Locale; light?: boolean }) {
  const c = L(d, "cta", l);
  return c && d.link ? <div className="mt-10 text-center"><Link href={localize(d.link, l)} className={`btn ${light ? "btn-ghost" : "btn-dark"}`}><span>{c}</span></Link></div> : null;
}

async function Products({ d, l, t, currency }: { d: D } & Ctx) {
  const take = Number(d.limit) || 8;
  const src = String(d.source ?? "new");
  const ps = src === "manual"
    ? await productsByIds((d.productIds as string[]) ?? [])
    : await findProducts({ new: { isNew: true }, featured: { isFeatured: true }, limited: { isLimited: true }, ready: { isReady: true } }[src] ?? {}, { createdAt: "desc" }, take);
  if (!ps.length) return null;
  return (
    <section className="py-24 md:py-36">
      <Head d={d} l={l} />
      <div className="mt-12">
        <Rail label={L(d, "title", l)}>
          {ps.map((p, i) => <div key={p.id} className="w-[72%] shrink-0 snap-start sm:w-[42%] lg:w-[calc(25%-1.15rem)]"><ProductCard p={toCard(p)} l={l} t={t} currency={currency} priority={i < 2} /></div>)}
        </Rail>
      </div>
      <Cta d={d} l={l} />
    </section>
  );
}

async function Collections({ d, l, t }: { d: D } & Ctx) {
  const ids = (d.collectionIds as string[]) ?? [];
  const cols = await db.collection.findMany({ where: { published: true, ...(ids.length ? { id: { in: ids } } : {}) }, orderBy: { sort: "asc" } });
  if (ids.length) cols.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
  if (!cols.length) return null;
  const media = await db.media.findMany({ where: { id: { in: cols.flatMap((c) => [c.coverId, c.coverMobileId]).filter(Boolean) as string[] } } });
  const get = (id: string | null) => { const x = media.find((q) => q.id === id); return x ? { url: x.url, kind: x.kind } : undefined; };
  const [first, ...rest] = cols;
  const Tile = ({ c, big }: { c: (typeof cols)[number]; big?: boolean }) => (
    <Link href={`/${l}/collections/${c.slug}`} className="group relative block overflow-hidden bg-ink text-ivory">
      <div className={`relative ${big ? "aspect-[4/5] md:aspect-[21/9]" : "aspect-[4/5]"}`}>
        <div className="absolute inset-0 transition duration-[1.6s] ease-[var(--ease-lux)] group-hover:scale-[1.04]">
          {big && get(c.coverMobileId) ? <><div className="absolute inset-0 hidden md:block"><Media m={get(c.coverId)} /></div><div className="absolute inset-0 md:hidden"><Media m={get(c.coverMobileId)} /></div></> : <Media m={get(c.coverId)} sizes={big ? "100vw" : "50vw"} />}
        </div>
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent" />
        <div className="absolute inset-x-0 bottom-0 p-8 text-center md:p-12">
          <h3 className={`display ${big ? "text-4xl md:text-6xl" : "text-3xl md:text-4xl"}`}>{L(c, "name", l)}</h3>
          {L(c, "desc", l) && <p className="mx-auto mt-3 line-clamp-2 max-w-md text-sm text-ivory/75">{L(c, "desc", l)}</p>}
          <span className="eyebrow link-u mt-5 inline-block">{t.shopNow}</span>
        </div>
      </div>
    </Link>
  );
  return (
    <section className="py-24 md:py-36">
      <Head d={d} l={l} />
      <div className="mt-12 space-y-4 px-4 md:space-y-6 md:px-10">
        <Tile c={first} big />
        {rest.length > 0 && <div className={`grid gap-4 md:gap-6 ${rest.length % 3 === 0 ? "md:grid-cols-3" : "md:grid-cols-2"}`}>{rest.map((c) => <Tile key={c.id} c={c} />)}</div>}
      </div>
    </section>
  );
}

function Occasions({ d, l }: { d: D } & Ctx) {
  const items = list(d, "items");
  if (!items.length) return null;
  return (
    <section className="py-24 md:py-36">
      <Head d={d} l={l} />
      <div className="no-scrollbar mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 md:grid md:grid-cols-[repeat(auto-fit,minmax(0,1fr))] md:gap-6 md:px-10">
        {items.map((it, i) => (
          <Link key={i} href={localize(String(it.link || "#"), l)} className="group relative aspect-[2/3] w-[70%] shrink-0 snap-start overflow-hidden bg-ink text-ivory md:w-auto">
            <div className="absolute inset-0 transition duration-[1.6s] ease-[var(--ease-lux)] group-hover:scale-105"><Media m={m(it.media, l)} sizes="(min-width:768px) 25vw, 70vw" /></div>
            <div className="absolute inset-0 bg-gradient-to-t from-black/55 to-transparent" />
            <p className="display absolute inset-x-0 bottom-8 text-center text-2xl md:text-3xl">{L(it, "label", l)}</p>
          </Link>
        ))}
      </div>
    </section>
  );
}

async function Ready({ d, l, t, currency }: { d: D } & Ctx) {
  const ps = await findProducts({ isReady: true, availability: { not: "out_of_stock" } }, { createdAt: "desc" }, Number(d.limit) || 4);
  if (!ps.length) return null;
  return (
    <section className="bg-sand py-24 md:py-36">
      <Head d={d} l={l} />
      <div className="mt-12 grid grid-cols-2 gap-x-4 gap-y-10 px-4 md:gap-x-6 md:px-10 lg:grid-cols-4">
        {ps.map((p) => <ProductCard key={p.id} p={toCard(p)} l={l} t={t} currency={currency} showDelivery />)}
      </div>
      <Cta d={d} l={l} />
    </section>
  );
}

function Story({ d, l }: { d: D } & Ctx) {
  const gallery = list(d, "gallery");
  return (
    <section className="bg-ink py-20 text-ivory md:py-32">
      <div className="mx-auto grid max-w-[1400px] items-center gap-12 px-4 md:grid-cols-2 md:gap-20 md:px-10">
        <div className="relative aspect-[4/5] overflow-hidden"><Media m={m(d.media, l)} sizes="50vw" /></div>
        <div><Head d={d} l={l} center={false} light /><div className="px-6 md:px-10 [&>div]:text-start"><Cta d={d} l={l} light /></div></div>
      </div>
      {gallery.length > 0 && (
        <div className="mx-auto mt-16 grid max-w-[1400px] grid-cols-2 gap-4 px-4 md:mt-24 md:grid-cols-4 md:gap-6 md:px-10">
          {gallery.map((g, i) => (
            <figure key={i}>
              <div className="relative aspect-square overflow-hidden"><Media m={m(g.media, l)} sizes="25vw" /></div>
              {L(g, "caption", l) && <figcaption className="eyebrow mt-3 text-ivory/60">{L(g, "caption", l)}</figcaption>}
            </figure>
          ))}
        </div>
      )}
    </section>
  );
}

function Campaign({ d, l }: { d: D } & Ctx) {
  const items = list(d, "items");
  const layout = String(d.layout ?? "full");
  if (layout === "full" && items[0]) {
    return (
      <section className="relative h-[90svh] min-h-[520px] overflow-hidden bg-ink text-ivory">
        <Media m={m(items[0].media, l)} />
        <div className="absolute inset-0 bg-black/30" />
        <div className="absolute inset-x-0 bottom-0 pb-20"><Head d={d} l={l} light /><Cta d={d} l={l} light /></div>
      </section>
    );
  }
  return (
    <section className="py-24 md:py-36">
      <Head d={d} l={l} />
      <div className={`mt-12 grid gap-4 px-4 md:gap-6 md:px-10 ${layout === "split" ? "md:grid-cols-2" : "grid-cols-2 md:grid-cols-3"}`}>
        {items.map((it, i) => (
          <figure key={i} className={layout === "grid" && i === 0 ? "col-span-2 row-span-2 md:col-span-1" : ""}>
            <div className={`relative overflow-hidden ${layout === "split" ? "aspect-[4/5]" : "aspect-[3/4]"}`}><Media m={m(it.media, l)} sizes="50vw" /></div>
            {L(it, "caption", l) && <figcaption className="eyebrow mt-3 text-stone">{L(it, "caption", l)}</figcaption>}
          </figure>
        ))}
      </div>
      <Cta d={d} l={l} />
    </section>
  );
}

function Values({ d, l }: { d: D } & Ctx) {
  const items = list(d, "items");
  return (
    <section className="border-y border-gold/40 bg-onyx py-24 text-ivory md:py-32">
      <Head d={d} l={l} light />
      <div className="mx-auto mt-16 grid max-w-[1200px] gap-px bg-gold/20 sm:grid-cols-2 lg:grid-cols-3">
        {items.map((it, i) => (
          <div key={i} className="bg-onyx px-8 py-12 text-center">
            <span className="display text-3xl text-gold-soft">{String(i + 1).padStart(2, "0")}</span>
            <h3 className="display mt-4 text-2xl">{L(it, "title", l)}</h3>
            <p className="mx-auto mt-3 max-w-xs text-sm leading-7 text-ivory/60">{L(it, "text", l)}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

function Text({ d, l }: { d: D } & Ctx) {
  const eyebrow = L(d, "eyebrow", l), title = L(d, "title", l), text = L(d, "text", l);
  return (
    <section className="mx-auto max-w-3xl px-6 pb-4 pt-24 text-center md:pb-8 md:pt-36">
      {eyebrow && <p className="eyebrow text-gold">{eyebrow}</p>}
      {title && <h2 className="display mt-5 text-4xl leading-tight md:text-6xl">{title}</h2>}
      <Ornament />
      {text && <p className="mx-auto mt-8 max-w-xl whitespace-pre-line text-base leading-8 text-stone md:text-lg md:leading-9">{text}</p>}
      <Cta d={d} l={l} />
    </section>
  );
}

export async function RenderSection({ type, data, ...ctx }: { type: string; data: string } & Ctx) {
  const d = json<D>(data, {});
  const { l } = ctx;
  switch (type) {
    case "hero": {
      const slides = list(d, "slides").map((s) => ({ desktop: m(s.media, l), mobile: m(s.mobileMedia, l), eyebrow: L(s, "eyebrow", l), heading: L(s, "heading", l), text: L(s, "text", l), cta: L(s, "cta", l), link: localize(String(s.link ?? ""), l) }));
      return <Hero slides={slides} />;
    }
    case "collections": return <Collections d={d} {...ctx} />;
    case "products": return <Products d={d} {...ctx} />;
    case "occasions": return <Occasions d={d} {...ctx} />;
    case "ready": return <Ready d={d} {...ctx} />;
    case "story": return <Story d={d} {...ctx} />;
    case "campaign": return <Campaign d={d} {...ctx} />;
    case "values": return <Values d={d} {...ctx} />;
    case "text": return <Text d={d} {...ctx} />;
    default: return null;
  }
}
