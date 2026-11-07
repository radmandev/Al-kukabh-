import { notFound } from "next/navigation";
import { dict, L, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";

const MAP = { shipping: "shippingPolicy", returns: "returns", privacy: "privacy", terms: "terms" } as const;

export default async function Policy({ params }: { params: Promise<{ locale: Locale; slug: string }> }) {
  const { locale: l, slug } = await params;
  if (!(slug in MAP)) notFound();
  const s = await getSettings();
  const t = dict(l);
  return (
    <article className="mx-auto max-w-2xl px-6 py-20 md:py-28">
      <h1 className="display text-center text-4xl md:text-5xl">{t[MAP[slug as keyof typeof MAP]]}</h1>
      <div className="mt-12 whitespace-pre-line text-sm leading-8 text-stone">{L(s.policies, slug, l) || "—"}</div>
    </article>
  );
}
