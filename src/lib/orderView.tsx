import Image from "next/image";
import type { Order } from "@prisma/client";
import { json, money } from "./format";
import type { Locale, T } from "./i18n";

type Item = { nameAr: string; nameEn: string; image?: string; size: string; color: string; qty: number; price: number };
const STATUS: Record<string, [string, string]> = {
  pending: ["قيد المراجعة", "Pending"], confirmed: ["مؤكد", "Confirmed"], processing: ["قيد التجهيز", "Being prepared"], cancelled: ["ملغي", "Cancelled"], completed: ["مكتمل", "Completed"],
  unfulfilled: ["لم يُشحن بعد", "Not shipped yet"], shipped: ["تم الشحن", "Shipped"], delivered: ["تم التوصيل", "Delivered"], returned: ["مُرتجع", "Returned"],
};
export const statusLabel = (s: string, l: Locale) => STATUS[s]?.[l === "ar" ? 0 : 1] ?? s;

/** Customer-facing order summary (confirmation, tracking, account). */
export function OrderSummary({ o, l, t }: { o: Order; l: Locale; t: T }) {
  const items = json<Item[]>(o.items, []);
  const f = (v: number) => money(v, o.currency, l);
  return (
    <div className="space-y-8">
      <div className="grid grid-cols-2 gap-6 border-y border-line py-6 text-sm sm:grid-cols-3">
        <div><p className="eyebrow text-stone">{t.orderNumber}</p><p className="mt-1">{o.number}</p></div>
        <div><p className="eyebrow text-stone">{t.status}</p><p className="mt-1">{statusLabel(o.status, l)}</p></div>
        <div><p className="eyebrow text-stone">{t.shipping}</p><p className="mt-1">{statusLabel(o.shipStatus, l)}{o.tracking && <span className="block text-stone" dir="ltr">{o.tracking}</span>}</p></div>
      </div>
      <ul className="divide-y divide-line">
        {items.map((x, i) => (
          <li key={i} className="flex gap-4 py-4 text-sm">
            <div className="relative aspect-[3/4] w-16 bg-sand">{x.image && <Image src={x.image} alt="" fill sizes="64px" className="object-cover" />}</div>
            <div className="flex-1"><p>{l === "ar" ? x.nameAr : x.nameEn}</p><p className="text-stone">{[x.size, x.color, `× ${x.qty}`].filter(Boolean).join(" · ")}</p></div>
            <p>{f(x.price * x.qty)}</p>
          </li>
        ))}
      </ul>
      <dl className="ms-auto max-w-xs space-y-2 text-sm">
        <div className="flex justify-between"><dt>{t.subtotal}</dt><dd>{f(o.subtotal)}</dd></div>
        {o.discount > 0 && <div className="flex justify-between"><dt>{t.discount}</dt><dd>−{f(o.discount)}</dd></div>}
        <div className="flex justify-between"><dt>{t.shipping}</dt><dd>{o.shipping ? f(o.shipping) : t.free}</dd></div>
        <div className="display flex justify-between border-t border-line pt-3 text-xl"><dt>{t.total}</dt><dd>{f(o.total)}</dd></div>
      </dl>
    </div>
  );
}
