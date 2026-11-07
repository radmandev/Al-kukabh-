import type { Metadata } from "next";
import Image from "next/image";
import { db } from "@/lib/db";
import { dict, L, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";
import { RenderSection } from "@/components/sections";

export async function generateMetadata({ params }: { params: Promise<{ locale: Locale }> }): Promise<Metadata> {
  return { title: dict((await params).locale).story };
}

// Reuses the story / campaign / values blocks managed in Admin → Homepage (even if hidden on the homepage).
export default async function Story({ params }: { params: Promise<{ locale: Locale }> }) {
  const { locale: l } = await params;
  const [s, secs] = await Promise.all([getSettings(), db.section.findMany({ where: { type: { in: ["story", "campaign", "values"] } }, orderBy: { sort: "asc" } })]);
  const t = dict(l);
  return (
    <>
      <header className="mx-auto max-w-2xl px-6 py-20 text-center md:py-32">
        <Image src={s.brand.logoUrl} alt="" width={120} height={120} className="mx-auto h-28 w-28 rounded-full object-cover" />
        <p className="eyebrow mt-10 text-gold">{t.story}</p>
        <h1 className="display mt-4 text-4xl md:text-6xl">{L(s.brand, "name", l)}</h1>
        <p className="mt-8 text-base leading-8 text-stone">{L(s.brand, "about", l)}</p>
      </header>
      {secs.map((x) => <RenderSection key={x.id} type={x.type} data={x.data} l={l} t={t} currency={s.currency} />)}
    </>
  );
}
