import Link from "next/link";
import { notFound } from "next/navigation";
import { db } from "@/lib/db";
import { json } from "@/lib/format";
import { ProductForm } from "@/components/admin/ProductForm";
import { PageHead, btn2 } from "@/components/admin/ui";

const blank = {
  nameAr: "", nameEn: "", slug: "", sku: "", descAr: "", descEn: "", fabricAr: "", fabricEn: "", detailsAr: "", detailsEn: "",
  careAr: "", careEn: "", deliveryAr: "", deliveryEn: "", price: 0, salePrice: null, stock: 0, availability: "in_stock" as const,
  sizes: ["52", "54", "56", "58", "60"], colors: [{ nameAr: "أسود", nameEn: "Black", hex: "#0e0e10" }],
  isNew: true, isFeatured: false, isLimited: false, isReady: false, published: true, seoTitle: "", seoDesc: "",
  categoryId: null, collectionId: null, media: [],
};

export default async function EditProduct({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const [categories, collections] = await Promise.all([db.category.findMany({ orderBy: { sort: "asc" } }), db.collection.findMany({ orderBy: { sort: "asc" } })]);
  let init;
  if (id === "new") init = blank;
  else {
    const p = await db.product.findUnique({ where: { id }, include: { media: { include: { media: true }, orderBy: { sort: "asc" } } } });
    if (!p) notFound();
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { createdAt, updatedAt, ...rest } = p;
    init = { ...rest, availability: p.availability as typeof blank.availability, sizes: json(p.sizes, []), colors: json(p.colors, []), media: p.media.map((x) => x.media) };
  }
  return (
    <>
      <PageHead title={id === "new" ? "New product" : init.nameEn}>
        <Link href="/admin/products" className={btn2}>← All products</Link>
        {id !== "new" && <Link href={`/en/product/${init.slug}`} target="_blank" className={btn2}>View ↗</Link>}
      </PageHead>
      <ProductForm key={id} init={init} categories={categories} collections={collections} />
    </>
  );
}
