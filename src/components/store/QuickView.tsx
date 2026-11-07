"use client";
import Link from "next/link";
import Image from "next/image";
import { useEffect } from "react";
import type { CardProduct } from "@/lib/catalog";
import { L, type Locale, type T } from "@/lib/i18n";
import { Icon } from "@/components/Icon";
import { AddToBag } from "./AddToBag";
import { Price } from "./ProductCard";

export function QuickView({ p, l, t, currency, onClose }: { p: CardProduct; l: Locale; t: T; currency: string; onClose: () => void }) {
  useEffect(() => {
    const k = (e: KeyboardEvent) => e.key === "Escape" && onClose();
    window.addEventListener("keydown", k); document.body.style.overflow = "hidden";
    return () => { window.removeEventListener("keydown", k); document.body.style.overflow = ""; };
  }, [onClose]);
  const imgs = p.gallery.filter((m) => m.kind === "image");
  return (
    <div className="fixed inset-0 z-[90] grid place-items-center p-4" role="dialog" aria-modal aria-label={L(p, "name", l)}>
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <div className="reveal relative grid max-h-[90vh] w-full max-w-4xl overflow-y-auto bg-ivory md:grid-cols-2">
        <button onClick={onClose} className="absolute end-4 top-4 z-10" aria-label={t.close}><Icon name="close" /></button>
        <div className="no-scrollbar flex snap-x snap-mandatory overflow-x-auto">
          {imgs.map((m) => <div key={m.url} className="relative aspect-[3/4] w-full shrink-0 snap-center"><Image src={m.url} alt={L(m, "alt", l)} fill sizes="50vw" className="object-cover" /></div>)}
        </div>
        <div className="p-8 md:p-10">
          <h2 className="display text-3xl">{L(p, "name", l)}</h2>
          <Price p={p} l={l} currency={currency} className="mt-3 text-lg" />
          <div className="mt-8"><AddToBag p={p} l={l} t={t} compact /></div>
          <Link href={`/${l}/product/${p.slug}`} className="link-u mt-8 inline-block text-sm">{t.details} →</Link>
        </div>
      </div>
    </div>
  );
}
