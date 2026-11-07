"use client";
import Link from "next/link";
import Image from "next/image";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import type { Locale, T } from "@/lib/i18n";
import { Icon } from "@/components/Icon";
import { useStore } from "./StoreProvider";

type Col = { slug: string; name: string; cover?: string };

export function Header({ l, t, brand, collections, signedIn }: { l: Locale; t: T; brand: { name: string; tagline: string; logo: string }; collections: Col[]; signedIn: boolean }) {
  const path = usePathname();
  const isHome = path === `/${l}`;
  const [scrolled, setScrolled] = useState(false);
  const [drawer, setDrawer] = useState(false);
  const [search, setSearch] = useState(false);
  const [mega, setMega] = useState(false);
  const { lines, setBagOpen, wish } = useStore();
  const count = lines.reduce((n, x) => n + x.qty, 0);

  useEffect(() => {
    const f = () => setScrolled(window.scrollY > 40);
    f(); window.addEventListener("scroll", f, { passive: true });
    return () => window.removeEventListener("scroll", f);
  }, []);
  useEffect(() => { setDrawer(false); setSearch(false); setMega(false); }, [path]);
  useEffect(() => { document.body.style.overflow = drawer || search ? "hidden" : ""; }, [drawer, search]);

  const other = l === "ar" ? "en" : "ar";
  const switchHref = `/${other}${path.slice(3)}`;
  const nav = [
    { href: `/${l}/shop`, label: t.shop },
    { href: `/${l}/shop?tag=new`, label: t.newArrivals },
    { href: `/${l}/collections`, label: t.collections, mega: true },
    { href: `/${l}/shop?category=occasion`, label: t.occasion },
    { href: `/${l}/shop?category=everyday`, label: t.everyday },
    { href: `/${l}/shop?tag=limited`, label: t.limited },
    { href: `/${l}/story`, label: t.story },
  ];

  return (
    <>
      {isHome && <div aria-hidden className="h-0" />}
      <header
        onMouseLeave={() => setMega(false)}
        className={`${isHome ? "fixed" : "sticky"} inset-x-0 top-0 z-50 transition-colors duration-500 ${scrolled ? "shadow-[0_8px_30px_rgba(0,0,0,.35)]" : ""} border-b border-gold/50 bg-onyx/95 text-gold-soft backdrop-blur`}
      >
        <div className="mx-auto grid h-20 max-w-[1600px] grid-cols-[1fr_auto_1fr] items-center px-4 md:h-24 md:px-10">
          <div className="flex items-center gap-4">
            <button className="lg:hidden" onClick={() => setDrawer(true)} aria-label={t.menu}><Icon name="menu" /></button>
            <button onClick={() => setSearch(true)} aria-label={t.search} className="flex items-center gap-2">
              <Icon name="search" /><span className="tracked hidden text-[.68rem] md:inline">{t.search}</span>
            </button>
          </div>
          <Link href={`/${l}`} className="flex items-center gap-3" aria-label={brand.name}>
            <Image src={brand.logo} alt={brand.name} width={160} height={160} priority
              className="h-16 w-16 rounded-full object-cover ring-2 ring-gold-soft shadow-[0_0_28px_rgba(214,189,138,.45)] transition-transform duration-500 hover:scale-105 md:h-[84px] md:w-[84px]" />
          </Link>
          <div className="flex items-center justify-end gap-4 md:gap-5">
            <Link href={switchHref} onClick={() => (document.cookie = `lang=${other};path=/;max-age=31536000`)} className="tracked text-[.68rem]">{other === "ar" ? "عربي" : "EN"}</Link>
            <Link href={`/${l}/account`} aria-label={t.account} className="hidden sm:block"><Icon name="user" /></Link>
            <Link href={`/${l}/wishlist`} aria-label={t.wishlist} className="relative hidden sm:block">
              <Icon name="heart" />{wish.length > 0 && <span className="absolute -end-1.5 -top-1 h-1.5 w-1.5 rounded-full bg-gold-soft" />}
            </Link>
            <button onClick={() => setBagOpen(true)} aria-label={`${t.bag} (${count})`} className="relative">
              <Icon name="bag" />
              {count > 0 && <span className="absolute -end-2 -top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-gold-soft px-1 text-[.6rem] font-medium text-ink">{count}</span>}
            </button>
          </div>
        </div>
        <nav className="hidden justify-center gap-9 pb-4 lg:flex" aria-label="Main">
          {nav.map((n) => (
            <Link key={n.href} href={n.href} onMouseEnter={() => setMega(!!n.mega)} className="tracked link-u text-[.68rem] transition-colors hover:text-ivory">{n.label}</Link>
          ))}
        </nav>
        {mega && collections.length > 0 && (
          <div className="absolute inset-x-0 top-full hidden border-b border-line bg-ivory text-ink lg:block reveal">
            <div className="mx-auto grid max-w-[1400px] grid-cols-4 gap-6 px-10 py-10">
              {collections.map((c) => (
                <Link key={c.slug} href={`/${l}/collections/${c.slug}`} className="group">
                  <div className="relative aspect-[4/5] overflow-hidden bg-sand">
                    {c.cover && <Image src={c.cover} alt="" fill sizes="25vw" className="object-cover transition duration-[1.4s] ease-[var(--ease-lux)] group-hover:scale-105" />}
                  </div>
                  <p className="display mt-4 text-xl">{c.name}</p>
                  <p className="eyebrow mt-1 text-stone">{t.discover}</p>
                </Link>
              ))}
            </div>
          </div>
        )}
      </header>

      {/* mobile drawer */}
      <div className={`fixed inset-0 z-[60] lg:hidden ${drawer ? "" : "pointer-events-none"}`} aria-hidden={!drawer}>
        <div onClick={() => setDrawer(false)} className={`absolute inset-0 bg-black/40 transition-opacity duration-500 ${drawer ? "opacity-100" : "opacity-0"}`} />
        <div className={`absolute inset-y-0 start-0 flex w-[86%] max-w-sm flex-col bg-ivory transition-transform duration-500 ease-[var(--ease-lux)] ${drawer ? "translate-x-0" : l === "ar" ? "translate-x-full" : "-translate-x-full"}`}>
          <div className="flex h-20 items-center justify-between border-b border-gold/40 bg-onyx px-5 text-gold-soft">
            <Image src={brand.logo} alt={brand.name} width={96} height={96} className="h-12 w-12 rounded-full object-cover ring-2 ring-gold-soft" />
            <button onClick={() => setDrawer(false)} aria-label={t.close}><Icon name="close" /></button>
          </div>
          <nav className="flex-1 overflow-y-auto px-5 py-6">
            {nav.map((n) => (
              <Link key={n.href} href={n.href} className="display flex items-center justify-between border-b border-line py-4 text-2xl">
                {n.label}<Icon name="chevron" className="h-4 w-4 opacity-40 rtl:rotate-180" />
              </Link>
            ))}
            {collections.length > 0 && (
              <div className="no-scrollbar -mx-5 mt-6 flex gap-3 overflow-x-auto px-5">
                {collections.map((c) => (
                  <Link key={c.slug} href={`/${l}/collections/${c.slug}`} className="w-36 shrink-0">
                    <div className="relative aspect-[3/4] bg-sand">{c.cover && <Image src={c.cover} alt="" fill sizes="144px" className="object-cover" />}</div>
                    <p className="mt-2 text-sm">{c.name}</p>
                  </Link>
                ))}
              </div>
            )}
          </nav>
          <div className="grid grid-cols-2 border-t border-line text-center text-sm">
            <Link href={`/${l}/account`} className="border-e border-line py-4">{signedIn ? t.account : t.login}</Link>
            <Link href={`/${l}/wishlist`} className="py-4">{t.wishlist}</Link>
          </div>
        </div>
      </div>

      {/* search overlay */}
      {search && (
        <div className="fixed inset-0 z-[70] bg-ivory/98 reveal" role="dialog" aria-label={t.search}>
          <button onClick={() => setSearch(false)} className="absolute end-5 top-5" aria-label={t.close}><Icon name="close" className="h-6 w-6" /></button>
          <form action={`/${l}/shop`} className="mx-auto mt-[22vh] max-w-2xl px-6">
            <p className="eyebrow mb-6 text-stone">{t.search}</p>
            <input autoFocus name="q" placeholder={t.searchPlaceholder} className="display w-full border-b border-ink bg-transparent pb-4 text-3xl outline-none md:text-5xl" />
          </form>
        </div>
      )}
    </>
  );
}
