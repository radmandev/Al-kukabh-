import Link from "next/link";
import type { Metadata } from "next";
import { db } from "@/lib/db";
import { dict, L, type Locale } from "@/lib/i18n";
import { Media } from "@/components/Media";

export async function generateMetadata({ params }: { params: Promise<{ locale: Locale }> }): Promise<Metadata> {
  return { title: dict((await params).locale).collections };
}

export default async function Collections({ params }: { params: Promise<{ locale: Locale }> }) {
  const { locale: l } = await params;
  const t = dict(l);
  const cols = await db.collection.findMany({ where: { published: true }, orderBy: { sort: "asc" } });
  const media = await db.media.findMany({ where: { id: { in: cols.map((c) => c.coverId).filter(Boolean) as string[] } } });
  return (
    <div className="pb-24">
      <header className="py-14 text-center md:py-20"><h1 className="display text-4xl md:text-6xl">{t.collections}</h1></header>
      <div className="space-y-4 px-4 md:space-y-6 md:px-10">
        {cols.map((c, i) => {
          const cv = media.find((x) => x.id === c.coverId);
          return (
            <Link key={c.id} href={`/${l}/collections/${c.slug}`} className={`group grid items-center gap-6 md:grid-cols-2 md:gap-16 ${i % 2 ? "md:[&>*:first-child]:order-2" : ""}`}>
              <div className="relative aspect-[4/5] overflow-hidden bg-sand md:aspect-[5/6]">
                <div className="absolute inset-0 transition duration-[1.6s] ease-[var(--ease-lux)] group-hover:scale-[1.03]"><Media m={cv} sizes="50vw" priority={i === 0} /></div>
              </div>
              <div className="px-2 pb-10 text-center md:pb-0">
                <p className="eyebrow text-gold">{String(i + 1).padStart(2, "0")}</p>
                <h2 className="display mt-3 text-4xl md:text-6xl">{L(c, "name", l)}</h2>
                <p className="mx-auto mt-5 max-w-md text-sm leading-7 text-stone">{L(c, "desc", l)}</p>
                <span className="eyebrow link-u mt-8 inline-block">{t.discover}</span>
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
