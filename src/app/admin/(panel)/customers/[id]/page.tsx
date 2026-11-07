import Link from "next/link";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { money } from "@/lib/format";
import { Badge, PageHead, btn2, card, toneFor } from "@/components/admin/ui";

export default async function Customer({ params }: { params: Promise<{ id: string }> }) {
  const u = await db.user.findUnique({ where: { id: (await params).id } });
  if (!u) notFound();
  const orders = await db.order.findMany({ where: { OR: [{ userId: u.id }, { email: u.email }] }, orderBy: { createdAt: "desc" } });
  const spent = orders.filter((o) => o.status !== "cancelled").reduce((s, o) => s + o.total, 0);
  return (
    <>
      <PageHead title={u.name}><Link href="/admin/customers" className={btn2}>← Customers</Link></PageHead>
      <div className="grid gap-6 lg:grid-cols-[320px_1fr]">
        <section className={`${card} space-y-1 text-sm`}>
          <p>{u.email}</p><p dir="ltr" className="text-start">{u.phone || "—"}</p>
          <p className="text-stone">Joined {u.createdAt.toLocaleDateString("en-GB")}</p>
          <p className="pt-3 font-medium">{orders.length} orders · {orders[0] ? money(spent, orders[0].currency, "en") : "—"} spent</p>
        </section>
        <section className={card}>
          <h2 className="mb-3 font-medium">Order history</h2>
          <ul className="divide-y divide-black/5 text-sm">
            {orders.map((o) => <li key={o.id}><Link href={`/admin/orders/${o.id}`} className="flex gap-4 py-2 hover:bg-black/[.02]"><span className="w-28 font-medium">{o.number}</span><span className="flex-1 text-stone">{o.createdAt.toLocaleDateString("en-GB")}</span><Badge tone={toneFor(o.status)}>{o.status}</Badge><span className="w-28 text-end">{money(o.total, o.currency, "en")}</span></Link></li>)}
          </ul>
        </section>
      </div>
    </>
  );
}
