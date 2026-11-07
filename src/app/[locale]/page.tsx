import { db } from "@/lib/db";
import { dict, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";
import { RenderSection } from "@/components/sections";

export const dynamic = "force-dynamic";

export default async function Home({ params }: { params: Promise<{ locale: Locale }> }) {
  const { locale: l } = await params;
  const [sections, s] = await Promise.all([db.section.findMany({ where: { published: true }, orderBy: { sort: "asc" } }), getSettings()]);
  return <>{sections.map((x) => <RenderSection key={x.id} type={x.type} data={x.data} l={l} t={dict(l)} currency={s.currency} />)}</>;
}
