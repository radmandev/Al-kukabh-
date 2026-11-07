"use client";
import Link from "next/link";
import Image from "next/image";
import { useEffect } from "react";
import type { Locale, T } from "@/lib/i18n";
import { money } from "@/lib/format";
import { Icon } from "@/components/Icon";
import { useStore } from "./StoreProvider";

export function BagDrawer({ l, t, currency }: { l: Locale; t: T; currency: string }) {
  const { lines, setQty, remove, bagOpen: open, setBagOpen } = useStore();
  const subtotal = lines.reduce((s, x) => s + x.price * x.qty, 0);
  useEffect(() => {
    if (!open) return;
    const k = (e: KeyboardEvent) => e.key === "Escape" && setBagOpen(false);
    window.addEventListener("keydown", k); return () => window.removeEventListener("keydown", k);
  }, [open, setBagOpen]);
  return (
    <div className={`fixed inset-0 z-[80] ${open ? "" : "pointer-events-none"}`} aria-hidden={!open}>
      <div onClick={() => setBagOpen(false)} className={`absolute inset-0 bg-black/40 transition-opacity duration-500 ${open ? "opacity-100" : "opacity-0"}`} />
      <aside role="dialog" aria-label={t.bag} className={`absolute inset-y-0 end-0 flex w-full max-w-md flex-col bg-ivory transition-transform duration-500 ease-[var(--ease-lux)] ${open ? "translate-x-0" : l === "ar" ? "-translate-x-full" : "translate-x-full"}`}>
        <div className="flex h-16 items-center justify-between border-b border-line px-6">
          <p className="tracked text-xs">{t.bag} ({lines.length})</p>
          <button onClick={() => setBagOpen(false)} aria-label={t.close}><Icon name="close" /></button>
        </div>
        {lines.length === 0 ? (
          <div className="grid flex-1 place-items-center p-10 text-center">
            <div><p className="display text-2xl">{t.emptyBag}</p>
              <Link href={`/${l}/shop`} onClick={() => setBagOpen(false)} className="btn btn-dark mt-8">{t.continueShopping}</Link></div>
          </div>
        ) : (
          <>
            <ul className="flex-1 divide-y divide-line overflow-y-auto px-6">
              {lines.map((x, i) => (
                <li key={i} className="flex gap-4 py-5">
                  <Link href={`/${l}/product/${x.slug}`} onClick={() => setBagOpen(false)} className="relative aspect-[3/4] w-24 shrink-0 bg-sand">
                    {x.image && <Image src={x.image} alt="" fill sizes="96px" className="object-cover" />}
                  </Link>
                  <div className="flex flex-1 flex-col text-sm">
                    <p className="display text-lg">{l === "ar" ? x.nameAr : x.nameEn}</p>
                    <p className="mt-1 text-stone">{[x.size, x.color].filter(Boolean).join(" · ")}</p>
                    <p className="mt-1">{money(x.price, currency, l)}</p>
                    <div className="mt-auto flex items-center justify-between pt-3">
                      <div className="flex items-center border border-line">
                        <button className="p-2" onClick={() => setQty(i, x.qty - 1)} aria-label="-"><Icon name="minus" className="h-3 w-3" /></button>
                        <span className="w-6 text-center">{x.qty}</span>
                        <button className="p-2" onClick={() => setQty(i, x.qty + 1)} aria-label="+"><Icon name="plus" className="h-3 w-3" /></button>
                      </div>
                      <button onClick={() => remove(i)} className="link-u text-xs text-stone">{t.remove}</button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
            <div className="border-t border-line p-6">
              <div className="flex justify-between text-sm"><span>{t.subtotal}</span><span>{money(subtotal, currency, l)}</span></div>
              <Link href={`/${l}/checkout`} onClick={() => setBagOpen(false)} className="btn btn-dark mt-5 w-full">{t.checkout}</Link>
            </div>
          </>
        )}
      </aside>
    </div>
  );
}
