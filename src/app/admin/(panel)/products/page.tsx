import Link from "next/link";
import { db } from "@/lib/db";
import { getSettings } from "@/lib/settings";
import { money } from "@/lib/format";
import { Thumb } from "@/components/admin/media";
import { Badge, PageHead, btn, card, inp } from "@/components/admin/ui";

export default async function Products({ searchParams }: { searchParams: Promise<{ q?: string }> }) {
  const { q } = await searchParams;
  const [ps, s] = await Promise.all([
    db.product.findMany({
      where: q ? { OR: [{ nameEn: { contains: q } }, { nameAr: { contains: q } }, { sku: { contains: q } }] } : {},
      include: { media: { include: { media: true }, orderBy: { sort: "asc" }, take: 1 }, collection: true }, orderBy: { createdAt: "desc" },
    }),
    getSettings(),
  ]);
  return (
    <>
      <PageHead title="Products"><form><input name="q" defaultValue={q} placeholder="Search name or SKU" className={inp} /></form><Link href="/admin/products/new" className={btn}>+ New product</Link></PageHead>
      <div className={`${card} overflow-x-auto p-0 md:p-0`}>
        <table className="w-full min-w-[760px] text-sm">
          <thead className="border-b border-black/10 text-start text-xs text-stone"><tr>{["", "Product", "SKU", "Price", "Stock", "Labels", "Status"].map((h) => <th key={h} className="p-3 text-start font-medium">{h}</th>)}</tr></thead>
          <tbody className="divide-y divide-black/5">
            {ps.map((p) => (
              <tr key={p.id} className="hover:bg-black/[.02]">
                <td className="w-16 p-3">{p.media[0] ? <Thumb m={p.media[0].media} className="aspect-[3/4] w-12 rounded" /> : <div className="aspect-[3/4] w-12 rounded bg-black/5" />}</td>
                <td className="p-3"><Link href={`/admin/products/${p.id}`} className="font-medium hover:underline">{p.nameEn}</Link><div className="text-xs text-stone" dir="rtl">{p.nameAr}</div><div className="text-xs text-stone">{p.collection?.nameEn}</div></td>
                <td className="p-3 text-stone">{p.sku}</td>
                <td className="p-3">{money(p.salePrice ?? p.price, s.currency, "en")}{p.salePrice != null && <s className="ms-2 text-xs text-stone">{money(p.price, s.currency, "en")}</s>}</td>
                <td className="p-3">{p.availability === "in_stock" ? <span className={p.stock <= 3 ? "text-red-700" : ""}>{p.stock}</span> : <span className="text-xs text-stone">{p.availability.replace(/_/g, " ")}</span>}</td>
                <td className="space-x-1 p-3">{p.isNew && <Badge>New</Badge>}{p.isFeatured && <Badge>Featured</Badge>}{p.isLimited && <Badge tone="gold">Limited</Badge>}{p.isReady && <Badge>Ready</Badge>}</td>
                <td className="p-3">{p.published ? <Badge tone="good">Published</Badge> : <Badge>Draft</Badge>}</td>
              </tr>
            ))}
          </tbody>
        </table>
        {!ps.length && <p className="p-10 text-center text-stone">No products yet.</p>}
      </div>
    </>
  );
}
