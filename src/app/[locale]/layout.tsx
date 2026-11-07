import type { Metadata } from "next";
import { notFound } from "next/navigation";
import "../globals.css";
import { fontVars } from "@/lib/fonts";
import { dict, dir, isLocale, L, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";
import { db } from "@/lib/db";
import { posterMap } from "@/lib/catalog";
import { currentUser } from "@/lib/auth";
import { StoreProvider } from "@/components/store/StoreProvider";
import { Header } from "@/components/store/Header";
import { Footer } from "@/components/store/Footer";
import { BagDrawer } from "@/components/store/BagDrawer";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const s = await getSettings();
  const l = (isLocale(locale) ? locale : "ar") as Locale;
  const title = L(s.seo, "title", l);
  return {
    metadataBase: new URL(process.env.SITE_URL || "http://localhost:3000"),
    title: { default: title, template: `%s — ${L(s.brand, "name", l)}` },
    description: L(s.seo, "desc", l),
    icons: { icon: s.brand.logoUrl },
    openGraph: { siteName: L(s.brand, "name", l), images: s.seo.ogImage ? [s.seo.ogImage] : [s.brand.logoUrl], locale: l === "ar" ? "ar_SA" : "en_US" },
    alternates: { languages: { ar: "/ar", en: "/en" } },
  };
}

export default async function LocaleLayout({ children, params }: { children: React.ReactNode; params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  if (!isLocale(locale)) notFound();
  const [s, cols, user] = await Promise.all([
    getSettings(),
    db.collection.findMany({ where: { published: true }, orderBy: { sort: "asc" }, take: 4 }),
    currentUser(),
  ]);
  const covers = await posterMap(cols.map((c) => c.coverId));
  const t = dict(locale);
  return (
    <html lang={locale} dir={dir(locale)} className={fontVars}>
      <body className="min-h-screen">
        <StoreProvider currency={s.currency}>
          <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:z-[100] focus:bg-ink focus:p-3 focus:text-ivory">Skip to content</a>
          <Header
            l={locale} t={t} brand={{ name: L(s.brand, "name", locale), tagline: L(s.brand, "tagline", locale), logo: s.brand.logoUrl }}
            signedIn={!!user}
            collections={cols.map((c) => ({ slug: c.slug, name: L(c, "name", locale), cover: c.coverId ? covers.get(c.coverId) : undefined }))}
          />
          <main id="main">{children}</main>
          <Footer l={locale} t={t} s={s} />
          <BagDrawer l={locale} t={t} currency={s.currency} />
        </StoreProvider>
      </body>
    </html>
  );
}
