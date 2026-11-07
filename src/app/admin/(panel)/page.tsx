import Link from "next/link";
import { db } from "@/lib/db";
import { getSettings } from "@/lib/settings";
import { money } from "@/lib/format";
import { Badge, PageHead, card, toneFor } from "@/components/admin/ui";

export default async function Overview() {
  const since = new Date(Date.now() - 30 * 864e5);
  const [s, recent, month, pending, products, low, customers, subs] = await Promise.all([
    getSettings(),
    db.order.findMany({ orderBy: { createdAt: "desc" }, take: 8 }),
    db.order.aggregate({ where: { createdAt: { gte: since }, status: { not: "cancelled" } }, _sum: { total: true }, _count: true }),
    db.order.count({ where: { status: "pending" } }),
    db.product.count({ where: { published: true } }),
    db.product.findMany({ where: { availability: "in_stock", stock: { lte: 3 }, published: true }, select: { id: true, nameEn: true, stock: true } }),
    db.user.count({ where: { role: "CUSTOMER" } }),
    db.subscriber.count(),
  ]);
  const stat = (label: string, value: React.ReactNode, href?: string) => {
    const body = <><p className="text-xs text-stone">{label}</p><p className="display mt-2 text-3xl">{value}</p></>;
    return href ? <Link href={href} className={`${card} hover:border-ink`}>{body}</Link> : <div className={card}>{body}</div>;
  };
  return (
    <>
      <PageHead title="Overview" />
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {stat("Revenue · 30 days", money(month._sum.total ?? 0, s.currency, "en"))}
        {stat("Orders · 30 days", month._count, "/admin/orders")}
        {stat("Awaiting review", pending, "/admin/orders?status=pending")}
        {stat("Live products", products, "/admin/products")}
        {stat("Customers", customers, "/admin/customers")}
        {stat("Newsletter subscribers", subs)}
      </div>
      <div className="mt-6 grid gap-6 lg:grid-cols-[2fr_1fr]">
        <section className={card}>
          <h2 className="mb-4 font-medium">Latest orders</h2>
          <ul className="divide-y divide-black/5 text-sm">
            {recent.map((o) => (
              <li key={o.id}><Link href={`/admin/orders/${o.id}`} className="flex flex-wrap items-center gap-3 py-2.5 hover:bg-black/[.02]">
                <span className="w-28 font-medium">{o.number}</span><span className="flex-1">{o.name}</span>
                <Badge tone={toneFor(o.status)}>{o.status}</Badge><span className="w-28 text-end">{money(o.total, o.currency, "en")}</span>
              </Link></li>
            ))}
            {!recent.length && <li className="py-6 text-stone">No orders yet.</li>}
          </ul>
        </section>
        <section className={card}>
          <h2 className="mb-4 font-medium">Low stock (≤ 3)</h2>
          <ul className="space-y-2 text-sm">
            {low.map((p) => <li key={p.id} className="flex justify-between"><Link href={`/admin/products/${p.id}`} className="hover:underline">{p.nameEn}</Link><span className="text-red-700">{p.stock}</span></li>)}
            {!low.length && <li className="text-stone">All good.</li>}
          </ul>
        </section>
      </div>
    </>
  );
}
