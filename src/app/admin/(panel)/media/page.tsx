import { db } from "@/lib/db";
import { MediaLibrary } from "@/components/admin/MediaLibrary";
import { PageHead } from "@/components/admin/ui";

export default async function MediaPage() {
  const items = await db.media.findMany({ orderBy: { createdAt: "desc" }, include: { _count: { select: { products: true } } } });
  return (
    <>
      <PageHead title="Media library" />
      <MediaLibrary items={items.map(({ _count, ...m }) => ({ ...m, used: _count.products }))} />
    </>
  );
}
