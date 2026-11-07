import Link from "next/link";
import { db } from "@/lib/db";
import { money } from "@/lib/format";
import { Badge, PageHead, card, inp, toneFor } from "@/components/admin/ui";

const STATUSES = ["pending", "confirmed", "processing", "completed", "cancelled"];

export default async function Orders({ searchParams }: { searchParams: Promise<{ status?: string; q?: string }> }) {
  const { status, q } = await searchParams;
  const orders = await db.order.findMany({
    where: { ...(status ? { status } : {}), ...(q ? { OR: [{ number: { contains: q.toUpperCase() } }, { email: { contains: q.toLowerCase() } }, { name: { contains: q } }, { phone: { contains: q } }] } : {}) },
    orderBy: { createdAt: "desc" }, take: 200,
  });
  return (
    <>
      <PageHead title="Orders">
        <form className="flex gap-2">
          <select name="status" defaultValue={status ?? ""} className={inp}><option value="">All statuses</option>{STATUSES.map((s) => <option key={s}>{s}</option>)}</select>
          <input name="q" defaultValue={q} placeholder="Order #, name, email, phone" className={inp} />
          <button className="rounded bg-ink px-3 text-sm text-ivory">Filter</button>
        </form>
      </PageHead>
      <div className={`${card} overflow-x-auto p-0 md:p-0`}>
        <table className="w-full min-w-[800px] text-sm">
          <thead className="border-b border-black/10 text-xs text-stone"><tr>{["Order", "Date", "Customer", "Total", "Payment", "Status", "Shipping"].map((h) => <th key={h} className="p-3 text-start font-medium">{h}</th>)}</tr></thead>
          <tbody className="divide-y divide-black/5">
            {orders.map((o) => (
              <tr key={o.id} className="hover:bg-black/[.02]">
                <td className="p-3"><Link href={`/admin/orders/${o.id}`} className="font-medium hover:underline">{o.number}</Link></td>
                <td className="p-3 text-stone">{o.createdAt.toLocaleString("en-GB", { dateStyle: "medium", timeStyle: "short" })}</td>
                <td className="p-3">{o.name}<div className="text-xs text-stone">{o.city}, {o.country}</div></td>
                <td className="p-3">{money(o.total, o.currency, "en")}</td>
                <td className="p-3"><Badge tone={toneFor(o.paymentStatus)}>{o.paymentStatus}</Badge> <span className="text-xs text-stone">{o.paymentMethod}</span></td>
                <td className="p-3"><Badge tone={toneFor(o.status)}>{o.status}</Badge></td>
                <td className="p-3"><Badge tone={toneFor(o.shipStatus)}>{o.shipStatus}</Badge></td>
              </tr>
            ))}
          </tbody>
        </table>
        {!orders.length && <p className="p-10 text-center text-stone">No orders found.</p>}
      </div>
    </>
  );
}
