import Link from "next/link";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { CollectionForm } from "@/components/admin/CollectionForm";
import { PageHead, btn2 } from "@/components/admin/ui";

export default async function EditCollection({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const products = await db.product.findMany({ orderBy: { createdAt: "desc" }, select: { id: true, nameEn: true, sku: true, collectionId: true } });
  let init;
  if (id === "new") init = { nameAr: "", nameEn: "", slug: "", descAr: "", descEn: "", cover: null, coverMobile: null, published: true, productIds: [] };
  else {
    const c = await db.collection.findUnique({ where: { id } });
    if (!c) notFound();
    const media = await db.media.findMany({ where: { id: { in: [c.coverId, c.coverMobileId].filter(Boolean) as string[] } } });
    init = { ...c, cover: media.find((m) => m.id === c.coverId) ?? null, coverMobile: media.find((m) => m.id === c.coverMobileId) ?? null, productIds: products.filter((p) => p.collectionId === c.id).map((p) => p.id) };
  }
  return (
    <>
      <PageHead title={id === "new" ? "New collection" : init.nameEn}><Link href="/admin/collections" className={btn2}>← All collections</Link></PageHead>
      <CollectionForm key={id} init={init} products={products.map((p) => ({ id: p.id, label: p.nameEn, sub: p.sku }))} />
    </>
  );
}
