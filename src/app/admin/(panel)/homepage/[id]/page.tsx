import Link from "next/link";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { json } from "@/lib/format";
import { SECTION_TYPES } from "@/lib/sections";
import { SectionEditor } from "@/components/admin/SectionEditor";
import { PageHead, btn2 } from "@/components/admin/ui";

export default async function EditSection({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const s = await db.section.findUnique({ where: { id } });
  if (!s || !SECTION_TYPES[s.type]) notFound();
  const [products, collections] = await Promise.all([
    db.product.findMany({ select: { id: true, nameEn: true, sku: true }, orderBy: { createdAt: "desc" } }),
    db.collection.findMany({ select: { id: true, nameEn: true }, orderBy: { sort: "asc" } }),
  ]);
  const def = SECTION_TYPES[s.type];
  return (
    <>
      <PageHead title="Edit section"><Link href="/admin/homepage" className={btn2}>← Homepage</Link></PageHead>
      <SectionEditor id={s.id} label={def.label} fields={def.fields} init={json(s.data, {})} published={s.published}
        opts={{ products: products.map((p) => ({ id: p.id, label: p.nameEn, sub: p.sku })), collections: collections.map((c) => ({ id: c.id, label: c.nameEn })) }} />
    </>
  );
}
