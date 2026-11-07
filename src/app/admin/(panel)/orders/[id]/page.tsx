import Link from "next/link";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { json, money } from "@/lib/format";
import { OrderStatusForm } from "@/components/admin/OrderStatusForm";
import { Thumb } from "@/components/admin/media";
import { PageHead, btn2, card } from "@/components/admin/ui";

type Item = { nameEn: string; nameAr: string; sku: string; image?: string; size: string; color: string; qty: number; price: number; productId: string };

export default async function OrderDetail({ params }: { params: Promise<{ id: string }> }) {
  const o = await db.order.findUnique({ where: { id: (await params).id }, include: { user: true } });
  if (!o) notFound();
  const items = json<Item[]>(o.items, []);
  const f = (v: number) => money(v, o.currency, "en");
  const history = json<{ at: string; note: string; by?: string }[]>(o.history, []);
  return (
    <>
      <PageHead title={`Order ${o.number}`}><Link href="/admin/orders" className={btn2}>← Orders</Link></PageHead>
      <div className="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div className="space-y-6">
          <section className={card}>
            <h2 className="mb-4 font-medium">Items</h2>
            <ul className="divide-y divide-black/5">
              {items.map((x, i) => (
                <li key={i} className="flex items-center gap-4 py-3 text-sm">
                  {x.image ? <Thumb m={{ url: x.image, kind: "image" }} className="aspect-[3/4] w-12 rounded" /> : <div className="aspect-[3/4] w-12 rounded bg-black/5" />}
                  <div className="flex-1"><Link href={`/admin/products/${x.productId}`} className="font-medium hover:underline">{x.nameEn}</Link><p className="text-xs text-stone">SKU {x.sku} · Size {x.size || "—"} · {x.color || "—"}</p></div>
                  <span className="text-stone">{f(x.price)} × {x.qty}</span><span className="w-24 text-end">{f(x.price * x.qty)}</span>
                </li>
              ))}
            </ul>
            <dl className="ms-auto mt-4 max-w-xs space-y-1 text-sm">
              <div className="flex justify-between"><dt>Subtotal</dt><dd>{f(o.subtotal)}</dd></div>
              {o.discount > 0 && <div className="flex justify-between"><dt>Discount ({o.discountCode})</dt><dd>−{f(o.discount)}</dd></div>}
              <div className="flex justify-between"><dt>Shipping</dt><dd>{f(o.shipping)}</dd></div>
              <div className="flex justify-between border-t border-black/10 pt-2 font-medium"><dt>Total</dt><dd>{f(o.total)}</dd></div>
            </dl>
          </section>
          <section className={`${card} grid gap-6 text-sm md:grid-cols-2`}>
            <div><h2 className="mb-2 font-medium">Customer</h2><p>{o.name}</p><p><a className="underline" href={`mailto:${o.email}`}>{o.email}</a></p><p dir="ltr" className="text-start"><a className="underline" href={`tel:${o.phone}`}>{o.phone}</a></p>
              {o.user && <Link href={`/admin/customers/${o.user.id}`} className="mt-2 inline-block text-xs underline">Customer account →</Link>}</div>
            <div><h2 className="mb-2 font-medium">Shipping address</h2><p>{o.address}</p><p>{o.city}, {o.country}</p>{o.notes && <p className="mt-2 rounded bg-amber-50 p-2">Note: {o.notes}</p>}</div>
            <div><h2 className="mb-2 font-medium">Payment</h2><p>{o.paymentMethod.toUpperCase()} · {o.paymentStatus}</p></div>
            <div><h2 className="mb-2 font-medium">Placed</h2><p>{o.createdAt.toLocaleString("en-GB")}</p></div>
          </section>
          <section className={card}>
            <h2 className="mb-4 font-medium">History</h2>
            <ol className="space-y-2 border-s border-black/10 ps-4 text-sm">
              {history.slice().reverse().map((h, i) => <li key={i}><span className="text-xs text-stone">{new Date(h.at).toLocaleString("en-GB")}{h.by && ` · ${h.by}`}</span><p>{h.note}</p></li>)}
            </ol>
          </section>
        </div>
        <section className={`${card} xl:sticky xl:top-6 xl:self-start`}><OrderStatusForm id={o.id} init={o} /></section>
      </div>
    </>
  );
}
