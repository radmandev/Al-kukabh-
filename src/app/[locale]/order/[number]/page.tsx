import Link from "next/link";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { currentUser } from "@/lib/auth";
import { dict, type Locale } from "@/lib/i18n";
import { OrderSummary } from "@/lib/orderView";
import { getSettings } from "@/lib/settings";

export const metadata = { robots: { index: false } };

export default async function OrderPage({ params, searchParams }: { params: Promise<{ locale: Locale; number: string }>; searchParams: Promise<{ e?: string }> }) {
  const [{ locale: l, number }, { e }] = await Promise.all([params, searchParams]);
  const t = dict(l);
  const [o, u, s] = await Promise.all([db.order.findUnique({ where: { number } }), currentUser(), getSettings()]);
  // only the buyer (by email link or account) may view
  if (!o || !(o.email === e?.toLowerCase() || (u && o.userId === u.id))) notFound();
  return (
    <div className="mx-auto max-w-2xl px-4 py-16 md:py-24">
      <p className="eyebrow text-center text-gold">{t.orderNumber} {o.number}</p>
      <h1 className="display mt-4 text-center text-3xl md:text-5xl">{t.orderPlaced}</h1>
      {o.paymentMethod === "bank" && o.paymentStatus === "pending" && (l === "ar" ? s.payments.bankDetailsAr : s.payments.bankDetailsEn) && (
        <p className="mt-8 whitespace-pre-line bg-sand p-6 text-sm leading-7">{l === "ar" ? s.payments.bankDetailsAr : s.payments.bankDetailsEn}</p>
      )}
      <div className="mt-12"><OrderSummary o={o} l={l} t={t} /></div>
      <div className="mt-12 text-center"><Link href={`/${l}/shop`} className="btn btn-dark">{t.continueShopping}</Link></div>
    </div>
  );
}
