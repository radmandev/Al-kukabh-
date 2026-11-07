import { db } from "@/lib/db";
import { saveCategory, deleteCategory } from "@/app/admin/actions";
import { SortableRows } from "@/components/admin/pickers";
import { PageHead, btn, btn2, card, danger, inp } from "@/components/admin/ui";

export default async function Categories() {
  const cats = await db.category.findMany({ orderBy: { sort: "asc" }, include: { _count: { select: { products: true } } } });
  return (
    <>
      <PageHead title="Categories" />
      <p className="-mt-4 mb-6 text-sm text-stone">Used for shop filters and menu links (e.g. occasion → /shop?category=occasion).</p>
      <SortableRows model="category" rows={cats.map((c) => ({ id: c.id, node: (
        <div className="flex flex-wrap items-center gap-2">
          <form action={saveCategory} className="flex flex-1 flex-wrap gap-2">
            <input type="hidden" name="id" value={c.id} />
            <input name="nameAr" defaultValue={c.nameAr} dir="rtl" className={`${inp} w-40`} aria-label="Arabic name" />
            <input name="nameEn" defaultValue={c.nameEn} className={`${inp} w-40`} aria-label="English name" />
            <input name="slug" defaultValue={c.slug} className={`${inp} w-36`} aria-label="Slug" />
            <button className={btn2}>Save</button>
          </form>
          <span className="text-xs text-stone">{c._count.products} products</span>
          <form action={deleteCategory.bind(null, c.id)}><button className={danger}>Delete</button></form>
        </div>
      ) }))} />
      <form action={saveCategory} className={`${card} mt-6 flex flex-wrap gap-2`}>
        <input name="nameAr" placeholder="الاسم بالعربية" dir="rtl" required className={`${inp} w-48`} />
        <input name="nameEn" placeholder="English name" required className={`${inp} w-48`} />
        <input name="slug" placeholder="slug (optional)" className={`${inp} w-40`} />
        <button className={btn}>+ Add category</button>
      </form>
    </>
  );
}
