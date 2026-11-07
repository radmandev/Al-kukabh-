"use client";
import { createContext, useContext, useEffect, useState, useCallback } from "react";
import type { CardProduct } from "@/lib/catalog";

export type Line = { productId: string; slug: string; nameAr: string; nameEn: string; image?: string; price: number; size: string; color: string; qty: number };
type Store = {
  lines: Line[]; add: (l: Line) => void; setQty: (i: number, q: number) => void; remove: (i: number) => void; clear: () => void;
  wish: CardProduct[]; toggleWish: (p: CardProduct) => void; inWish: (id: string) => boolean;
  recent: CardProduct[]; pushRecent: (p: CardProduct) => void;
  bagOpen: boolean; setBagOpen: (b: boolean) => void;
  currency: string;
};
const Ctx = createContext<Store | null>(null);
export const useStore = () => useContext(Ctx)!;

// ponytail: bag/wishlist/recent live in localStorage (per device); prices are re-checked server-side at checkout.
function usePersist<T>(key: string, init: T) {
  const [v, setV] = useState<T>(init);
  const [ready, setReady] = useState(false);
  useEffect(() => {
    try { const s = localStorage.getItem(key); if (s) setV(JSON.parse(s)); } catch {}
    setReady(true);
  }, [key]);
  useEffect(() => { if (ready) try { localStorage.setItem(key, JSON.stringify(v)); } catch {} }, [key, v, ready]);
  return [v, setV] as const;
}

export function StoreProvider({ children, currency }: { children: React.ReactNode; currency: string }) {
  const [lines, setLines] = usePersist<Line[]>("kw_bag", []);
  const [wish, setWish] = usePersist<CardProduct[]>("kw_wish", []);
  const [recent, setRecent] = usePersist<CardProduct[]>("kw_recent", []);
  const [bagOpen, setBagOpen] = useState(false);

  const add = useCallback((l: Line) => setLines((ls) => {
    const i = ls.findIndex((x) => x.productId === l.productId && x.size === l.size && x.color === l.color);
    if (i < 0) return [...ls, l];
    return ls.map((x, j) => (j === i ? { ...x, qty: Math.min(x.qty + l.qty, 10) } : x));
  }), [setLines]);

  return (
    <Ctx.Provider value={{
      lines, add,
      setQty: (i, q) => setLines((ls) => ls.map((x, j) => (j === i ? { ...x, qty: Math.max(1, Math.min(q, 10)) } : x))),
      remove: (i) => setLines((ls) => ls.filter((_, j) => j !== i)),
      clear: () => setLines([]),
      wish, inWish: (id) => wish.some((w) => w.id === id),
      toggleWish: (p) => setWish((w) => (w.some((x) => x.id === p.id) ? w.filter((x) => x.id !== p.id) : [p, ...w])),
      recent, pushRecent: (p) => setRecent((r) => [p, ...r.filter((x) => x.id !== p.id)].slice(0, 12)),
      bagOpen, setBagOpen, currency,
    }}>{children}</Ctx.Provider>
  );
}
