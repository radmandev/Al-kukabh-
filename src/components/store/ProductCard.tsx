"use client";
import Link from "next/link";
import Image from "next/image";
import { useState } from "react";
import type { CardProduct } from "@/lib/catalog";
import { L, type Locale, type T } from "@/lib/i18n";
import { money } from "@/lib/format";
import { Icon } from "@/components/Icon";
import { useStore } from "./StoreProvider";
import { QuickView } from "./QuickView";

export function Price({ p, l, currency, className = "" }: { p: Pick<CardProduct, "price" | "salePrice">; l: Locale; currency: string; className?: string }) {
  return (
    <span className={`flex flex-wrap items-baseline gap-2 ${className}`}>
      <span>{money(p.salePrice ?? p.price, currency, l)}</span>
      {p.salePrice != null && <s className="text-stone text-[.9em]" aria-label="was">{money(p.price, currency, l)}</s>}
    </span>
  );
}

export function ProductCard({ p, l, t, currency, priority, showDelivery }: { p: CardProduct; l: Locale; t: T; currency: string; priority?: boolean; showDelivery?: boolean }) {
  const { toggleWish, inWish } = useStore();
  const [qv, setQv] = useState(false);
  const imgs = p.gallery.filter((m) => m.kind === "image");
  const [a, b] = imgs;
  const name = L(p, "name", l);
  const wished = inWish(p.id);
  return (
    <div className="group relative">
      <div className="relative aspect-[3/4] overflow-hidden bg-sand">
        <Link href={`/${l}/product/${p.slug}`} className="absolute inset-0" aria-label={name}>
          {a && <Image src={a.url} alt={L(a, "alt", l) || name} fill priority={priority} sizes="(min-width:1024px) 25vw, 50vw" className={`object-cover transition duration-[1.2s] ease-[var(--ease-lux)] ${b ? "group-hover:opacity-0" : "group-hover:scale-[1.03]"}`} />}
          {b && <Image src={b.url} alt="" fill sizes="(min-width:1024px) 25vw, 50vw" className="object-cover opacity-0 transition duration-[1.2s] ease-[var(--ease-lux)] group-hover:scale-[1.03] group-hover:opacity-100" />}
        </Link>
        <div className="pointer-events-none absolute start-3 top-3 flex flex-col items-start gap-1.5">
          {p.isNew && <span className="eyebrow bg-ivory/90 px-2 py-1 text-[.58rem]">{t.new}</span>}
          {p.isLimited && <span className="eyebrow bg-ink px-2 py-1 text-[.58rem] text-gold-soft">{t.limitedLabel}</span>}
          {p.availability === "out_of_stock" && <span className="eyebrow bg-ivory/90 px-2 py-1 text-[.58rem] text-stone">{t.outOfStock}</span>}
        </div>
        <button onClick={() => toggleWish(p)} aria-pressed={wished} aria-label={t.addToWishlist} className="absolute end-3 top-3 p-1">
          <Icon name="heart" filled={wished} className={`h-5 w-5 ${wished ? "text-gold" : ""}`} />
        </button>
        <button onClick={() => setQv(true)} className="tracked absolute inset-x-3 bottom-3 hidden translate-y-2 bg-ivory/95 py-3 text-[.62rem] opacity-0 transition duration-500 group-hover:translate-y-0 group-hover:opacity-100 focus:opacity-100 md:block">
          {t.quickView}
        </button>
      </div>
      <div className="pt-4 text-center">
        <Link href={`/${l}/product/${p.slug}`} className="display block text-lg leading-snug md:text-xl">{name}</Link>
        <Price p={p} l={l} currency={currency} className="mt-1 justify-center text-sm text-ink/80" />
        {showDelivery && (
          <p className="mt-2 flex items-center justify-center gap-1.5 text-xs text-stone">
            <Icon name="truck" className="h-4 w-4" />{L(p, "delivery", l) || (p.availability === "in_stock" ? t.ready : t.madeToOrder)}
          </p>
        )}
      </div>
      {qv && <QuickView p={p} l={l} t={t} currency={currency} onClose={() => setQv(false)} />}
    </div>
  );
}
