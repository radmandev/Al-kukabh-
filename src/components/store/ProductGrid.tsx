import type { Product } from "@/lib/catalog";
import { toCard } from "@/lib/catalog";
import type { Locale, T } from "@/lib/i18n";
import { ProductCard } from "./ProductCard";

export function ProductGrid({ ps, l, t, currency }: { ps: Product[]; l: Locale; t: T; currency: string }) {
  if (!ps.length) return <p className="display py-32 text-center text-2xl text-stone">{t.noResults}</p>;
  return (
    <div className="grid grid-cols-2 gap-x-3 gap-y-12 md:gap-x-6 lg:grid-cols-3 xl:grid-cols-4">
      {ps.map((p, i) => <ProductCard key={p.id} p={toCard(p)} l={l} t={t} currency={currency} priority={i < 4} />)}
    </div>
  );
}
