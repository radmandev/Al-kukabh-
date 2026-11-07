import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { findProducts } from "@/lib/catalog";
import { dict, L, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";
import { ResponsiveMedia } from "@/components/Media";
import { ProductGrid } from "@/components/store/ProductGrid";

type P = { params: Promise<{ locale: Locale; slug: string }> };
const get = async (slug: string) => db.collection.findFirst({ where: { slug: decodeURIComponent(slug), published: true } });

export async function generateMetadata({ params }: P): Promise<Metadata> {
  const { locale, slug } = await params;
  const c = await get(slug);
  return c ? { title: L(c, "name", locale), description: L(c, "desc", locale) } : {};
}

export default async function CollectionPage({ params }: P) {
  const { locale: l, slug } = await params;
  const c = await get(slug);
  if (!c) notFound();
  const [ps, s, media] = await Promise.all([
    findProducts({ collectionId: c.id }), getSettings(),
    db.media.findMany({ where: { id: { in: [c.coverId, c.coverMobileId].filter(Boolean) as string[] } } }),
  ]);
  const cover = media.find((m) => m.id === c.coverId), mob = media.find((m) => m.id === c.coverMobileId);
  return (
    <>
      <section className="relative h-[70svh] min-h-[420px] overflow-hidden bg-ink text-ivory">
        <div className="kenburns absolute inset-0"><ResponsiveMedia desktop={cover} mobile={mob} priority /></div>
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-black/10" />
        <div className="absolute inset-x-0 bottom-0 mx-auto max-w-2xl px-6 pb-16 text-center">
          <p className="eyebrow reveal text-gold-soft">{dict(l).collections}</p>
          <h1 className="display reveal mt-3 text-5xl md:text-7xl">{L(c, "name", l)}</h1>
          {L(c, "desc", l) && <p className="reveal mt-5 text-sm leading-7 text-ivory/80">{L(c, "desc", l)}</p>}
        </div>
      </section>
      <div className="mx-auto max-w-[1600px] px-4 py-16 md:px-10 md:py-24">
        <ProductGrid ps={ps} l={l} t={dict(l)} currency={s.currency} />
      </div>
    </>
  );
}
