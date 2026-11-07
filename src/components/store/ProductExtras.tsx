"use client";
import { useEffect, useState } from "react";
import type { CardProduct } from "@/lib/catalog";
import { L, type Locale, type T } from "@/lib/i18n";
import { useStore } from "./StoreProvider";
import { ProductCard, Price } from "./ProductCard";

export function RecentlyViewed({ p, l, t, currency }: { p: CardProduct; l: Locale; t: T; currency: string }) {
  const { recent, pushRecent } = useStore();
  const [shown, setShown] = useState<CardProduct[]>([]);
  useEffect(() => {
    setShown(recent.filter((x) => x.id !== p.id).slice(0, 4));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [p.id, recent.length]);
  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => { pushRecent(p); }, [p.id]);
  if (!shown.length) return null;
  return (
    <section className="mx-auto max-w-[1600px] px-4 py-16 md:px-10">
      <h2 className="display mb-10 text-center text-3xl md:text-4xl">{t.recent}</h2>
      <div className="grid grid-cols-2 gap-x-3 gap-y-10 md:gap-x-6 lg:grid-cols-4">{shown.map((x) => <ProductCard key={x.id} p={x} l={l} t={t} currency={currency} />)}</div>
    </section>
  );
}

/** Mobile-only bar that appears once the main buy block scrolls out of view. */
export function StickyBuy({ p, l, t, currency }: { p: CardProduct; l: Locale; t: T; currency: string }) {
  const [show, setShow] = useState(false);
  useEffect(() => {
    const el = document.getElementById("buy");
    if (!el) return;
    const io = new IntersectionObserver(([e]) => setShow(!e.isIntersecting && e.boundingClientRect.top < 0));
    io.observe(el); return () => io.disconnect();
  }, []);
  return (
    <div className={`fixed inset-x-0 bottom-0 z-40 flex items-center gap-4 border-t border-line bg-ivory/95 px-4 py-3 backdrop-blur transition-transform duration-500 md:hidden ${show ? "" : "translate-y-full"}`}>
      <div className="min-w-0 flex-1"><p className="truncate text-sm">{L(p, "name", l)}</p><Price p={p} l={l} currency={currency} className="text-xs text-stone" /></div>
      <button onClick={() => document.getElementById("buy")?.scrollIntoView({ behavior: "smooth", block: "center" })} className="btn btn-dark px-6 py-3">{t.addToBag}</button>
    </div>
  );
}
