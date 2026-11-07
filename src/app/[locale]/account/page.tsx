import Link from "next/link";
import { db } from "@/lib/db";
import { currentUser } from "@/lib/auth";
import { dict, type Locale } from "@/lib/i18n";
import { money } from "@/lib/format";
import { statusLabel } from "@/lib/orderView";
import { AuthForms } from "@/components/store/AuthForms";
import { logout } from "../actions";

export const metadata = { robots: { index: false } };

export default async function Account({ params, searchParams }: { params: Promise<{ locale: Locale }>; searchParams: Promise<{ next?: string }> }) {
  const [{ locale: l }, { next }] = await Promise.all([params, searchParams]);
  const t = dict(l);
  const u = await currentUser();
  if (!u) return <AuthForms l={l} t={t} next={next?.startsWith("/") && !next.startsWith("//") ? next : undefined} />;
  const orders = await db.order.findMany({ where: { OR: [{ userId: u.id }, { email: u.email }] }, orderBy: { createdAt: "desc" } });
  return (
    <div className="mx-auto max-w-4xl px-4 py-16 md:py-24">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div><p className="eyebrow text-gold">{t.account}</p><h1 className="display mt-2 text-4xl">{u.name}</h1></div>
        <div className="flex gap-6 text-sm">
          {u.role !== "CUSTOMER" && <Link href="/admin" className="link-u">Dashboard</Link>}
          <form action={logout}><input type="hidden" name="locale" value={l} /><button className="link-u">{t.logout}</button></form>
        </div>
      </div>
      <h2 className="tracked mt-16 text-xs">{t.myOrders}</h2>
      {orders.length === 0 ? <p className="mt-6 text-stone">—</p> : (
        <ul className="mt-6 divide-y divide-line border-y border-line">
          {orders.map((o) => (
            <li key={o.id}>
              <Link href={`/${l}/order/${o.number}?e=${encodeURIComponent(o.email)}`} className="grid grid-cols-2 gap-2 py-5 text-sm hover:bg-sand sm:grid-cols-4">
                <span>{o.number}</span><span className="text-stone">{o.createdAt.toLocaleDateString(l)}</span>
                <span>{statusLabel(o.status, l)}</span><span className="text-end">{money(o.total, o.currency, l)}</span>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
