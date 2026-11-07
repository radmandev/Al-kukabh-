"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import type { CardProduct } from "@/lib/catalog";
import { L, type Locale, type T } from "@/lib/i18n";
import { Icon } from "@/components/Icon";
import { useStore } from "./StoreProvider";

/** Size / colour / qty selection + Add to bag, Buy now, Wishlist. Shared by product page and quick view. */
export function AddToBag({ p, l, t, compact }: { p: CardProduct; l: Locale; t: T; compact?: boolean }) {
  const { add, setBagOpen, toggleWish, inWish } = useStore();
  const router = useRouter();
  const [size, setSize] = useState(p.sizes.length === 1 ? p.sizes[0] : "");
  const [color, setColor] = useState(p.colors[0] ? L(p.colors[0], "name", l) : "");
  const [qty, setQty] = useState(1);
  const [err, setErr] = useState("");
  const sold = p.availability === "out_of_stock";

  const go = (buy?: boolean) => {
    if (p.sizes.length && !size) return setErr(t.selectSize);
    setErr("");
    const img = p.gallery.find((m) => m.kind === "image")?.url;
    add({ productId: p.id, slug: p.slug, nameAr: p.nameAr, nameEn: p.nameEn, image: img, price: p.salePrice ?? p.price, size, color, qty });
    if (buy) router.push(`/${l}/checkout`); else setBagOpen(true);
  };

  return (
    <div className="space-y-6">
      {p.colors.length > 0 && (
        <fieldset>
          <legend className="eyebrow mb-3 text-stone">{t.color}: <span className="text-ink">{color}</span></legend>
          <div className="flex gap-3">
            {p.colors.map((c) => {
              const n = L(c, "name", l);
              return <button key={n} type="button" onClick={() => setColor(n)} aria-label={n} aria-pressed={color === n}
                className={`h-8 w-8 rounded-full border-2 ${color === n ? "border-ink" : "border-transparent"} p-0.5`}><span className="block h-full w-full rounded-full ring-1 ring-line" style={{ background: c.hex }} /></button>;
            })}
          </div>
        </fieldset>
      )}
      {p.sizes.length > 0 && (
        <fieldset>
          <legend className="eyebrow mb-3 text-stone">{t.size}</legend>
          <div className="flex flex-wrap gap-2">
            {p.sizes.map((s) => (
              <button key={s} type="button" onClick={() => { setSize(s); setErr(""); }} aria-pressed={size === s}
                className={`min-w-12 border px-3 py-2.5 text-sm transition ${size === s ? "border-ink bg-ink text-ivory" : "border-line hover:border-ink"}`}>{s}</button>
            ))}
          </div>
          {err && <p role="alert" className="mt-2 text-sm text-red-700">{err}</p>}
        </fieldset>
      )}
      {!compact && (
        <div className="flex items-center gap-4">
          <span className="eyebrow text-stone">{t.qty}</span>
          <div className="flex items-center border border-line">
            <button type="button" className="p-3" onClick={() => setQty(Math.max(1, qty - 1))} aria-label="-"><Icon name="minus" className="h-3 w-3" /></button>
            <span className="w-8 text-center" aria-live="polite">{qty}</span>
            <button type="button" className="p-3" onClick={() => setQty(Math.min(10, qty + 1))} aria-label="+"><Icon name="plus" className="h-3 w-3" /></button>
          </div>
        </div>
      )}
      <div className="flex gap-2">
        <button disabled={sold} onClick={() => go()} className="btn btn-dark flex-1">{sold ? t.outOfStock : t.addToBag}</button>
        <button onClick={() => toggleWish(p)} aria-pressed={inWish(p.id)} aria-label={t.addToWishlist} className="border border-line px-4 hover:border-ink">
          <Icon name="heart" filled={inWish(p.id)} className={inWish(p.id) ? "text-gold" : ""} />
        </button>
      </div>
      {!compact && !sold && <button onClick={() => go(true)} className="btn w-full">{t.buyNow}</button>}
    </div>
  );
}
