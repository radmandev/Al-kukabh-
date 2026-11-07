"use client";
import Link from "next/link";
import { use } from "react";
import { dict, type Locale } from "@/lib/i18n";
import { useStore } from "@/components/store/StoreProvider";
import { ProductCard } from "@/components/store/ProductCard";

export default function Wishlist({ params }: { params: Promise<{ locale: Locale }> }) {
  const { locale: l } = use(params);
  const t = dict(l);
  const { wish, currency } = useStore();
  return (
    <div className="mx-auto max-w-[1600px] px-4 pb-24 md:px-10">
      <h1 className="display py-14 text-center text-4xl md:py-20 md:text-6xl">{t.wishlist}</h1>
      {wish.length === 0
        ? <div className="py-20 text-center"><p className="text-stone">{t.emptyWishlist}</p><Link href={`/${l}/shop`} className="btn btn-dark mt-8">{t.continueShopping}</Link></div>
        : <div className="grid grid-cols-2 gap-x-3 gap-y-12 md:gap-x-6 lg:grid-cols-4">{wish.map((p) => <ProductCard key={p.id} p={p} l={l} t={t} currency={currency} />)}</div>}
    </div>
  );
}
