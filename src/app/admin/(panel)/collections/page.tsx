import Link from "next/link";
import { db } from "@/lib/db";
import { Thumb } from "@/components/admin/media";
import { SortableRows } from "@/components/admin/pickers";
import { Badge, PageHead, btn } from "@/components/admin/ui";

export default async function Collections() {
  const cols = await db.collection.findMany({ orderBy: { sort: "asc" }, include: { _count: { select: { products: true } } } });
  const covers = await db.media.findMany({ where: { id: { in: cols.map((c) => c.coverId).filter(Boolean) as string[] } } });
  return (
    <>
      <PageHead title="Collections"><Link href="/admin/collections/new" className={btn}>+ New collection</Link></PageHead>
      <SortableRows model="collection" rows={cols.map((c) => {
        const cv = covers.find((m) => m.id === c.coverId);
        return { id: c.id, node: (
          <Link href={`/admin/collections/${c.id}`} className="flex items-center gap-4">
            {cv ? <Thumb m={cv} className="aspect-[4/5] w-12 rounded" /> : <div className="aspect-[4/5] w-12 rounded bg-black/5" />}
            <div className="flex-1"><p className="font-medium">{c.nameEn} <span className="text-stone" dir="rtl">· {c.nameAr}</span></p><p className="text-xs text-stone">{c._count.products} products · /{c.slug}</p></div>
            {c.published ? <Badge tone="good">Published</Badge> : <Badge>Hidden</Badge>}
          </Link>
        ) };
      })} />
    </>
  );
}
