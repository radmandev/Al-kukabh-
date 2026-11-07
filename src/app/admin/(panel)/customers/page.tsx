import Link from "next/link";
import { db } from "@/lib/db";
import { PageHead, card, inp } from "@/components/admin/ui";

export default async function Customers({ searchParams }: { searchParams: Promise<{ q?: string }> }) {
  const { q } = await searchParams;
  const users = await db.user.findMany({
    where: { role: "CUSTOMER", ...(q ? { OR: [{ name: { contains: q } }, { email: { contains: q.toLowerCase() } }, { phone: { contains: q } }] } : {}) },
    include: { _count: { select: { orders: true } } }, orderBy: { createdAt: "desc" },
  });
  // guest buyers (no account), grouped by email
  const guests = await db.order.groupBy({ by: ["email", "name"], where: { userId: null }, _count: true, _sum: { total: true }, orderBy: { _count: { email: "desc" } }, take: 100 });
  return (
    <>
      <PageHead title="Customers"><form><input name="q" defaultValue={q} placeholder="Search" className={inp} /></form></PageHead>
      <section className={`${card} overflow-x-auto`}>
        <h2 className="mb-3 font-medium">Registered ({users.length})</h2>
        <table className="w-full min-w-[600px] text-sm"><tbody className="divide-y divide-black/5">
          {users.map((u) => (
            <tr key={u.id}><td className="py-2"><Link href={`/admin/customers/${u.id}`} className="font-medium hover:underline">{u.name}</Link></td><td className="text-stone">{u.email}</td><td dir="ltr" className="text-stone">{u.phone}</td><td>{u._count.orders} orders</td><td className="text-stone">{u.createdAt.toLocaleDateString("en-GB")}</td></tr>
          ))}
        </tbody></table>
      </section>
      <section className={`${card} mt-6 overflow-x-auto`}>
        <h2 className="mb-3 font-medium">Guest buyers</h2>
        <table className="w-full min-w-[500px] text-sm"><tbody className="divide-y divide-black/5">
          {guests.map((g) => <tr key={g.email + g.name}><td className="py-2">{g.name}</td><td className="text-stone"><Link href={`/admin/orders?q=${encodeURIComponent(g.email)}`} className="hover:underline">{g.email}</Link></td><td>{g._count} orders</td></tr>)}
        </tbody></table>
      </section>
    </>
  );
}
