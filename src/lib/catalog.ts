import "server-only";
import { db } from "./db";
import { json } from "./format";
import type { Prisma } from "@prisma/client";

export const productInclude = {
  media: { include: { media: true }, orderBy: { sort: "asc" } },
  collection: true,
  category: true,
} satisfies Prisma.ProductInclude;

type Raw = Prisma.ProductGetPayload<{ include: typeof productInclude }>;
export type Color = { nameAr: string; nameEn: string; hex: string };

export async function posterMap(ids: (string | null | undefined)[]) {
  const list = ids.filter(Boolean) as string[];
  if (!list.length) return new Map<string, string>();
  const ms = await db.media.findMany({ where: { id: { in: list } } });
  return new Map(ms.map((m) => [m.id, m.url]));
}

export function shape(p: Raw) {
  return {
    ...p,
    sizes: json<string[]>(p.sizes, []),
    colors: json<Color[]>(p.colors, []),
    gallery: p.media.map((pm) => pm.media),
  };
}
export type Product = ReturnType<typeof shape>;
export type CardProduct = Pick<Product, "id" | "slug" | "nameAr" | "nameEn" | "price" | "salePrice" | "isNew" | "isLimited" | "availability" | "deliveryAr" | "deliveryEn" | "sizes" | "colors"> & {
  gallery: { url: string; kind: string; altAr: string; altEn: string }[];
};

export const toCard = (p: Product): CardProduct => ({
  id: p.id, slug: p.slug, nameAr: p.nameAr, nameEn: p.nameEn, price: p.price, salePrice: p.salePrice,
  isNew: p.isNew, isLimited: p.isLimited, availability: p.availability, deliveryAr: p.deliveryAr, deliveryEn: p.deliveryEn, sizes: p.sizes, colors: p.colors,
  gallery: p.gallery.map(({ url, kind, altAr, altEn }) => ({ url, kind, altAr, altEn })),
});

export async function findProducts(where: Prisma.ProductWhereInput = {}, orderBy: Prisma.ProductOrderByWithRelationInput = { createdAt: "desc" }, take?: number) {
  const rows = await db.product.findMany({ where: { published: true, ...where }, include: productInclude, orderBy, take });
  return rows.map(shape);
}

export async function productsByIds(ids: string[]) {
  const ps = await findProducts({ id: { in: ids } });
  return ids.map((id) => ps.find((p) => p.id === id)).filter(Boolean) as Product[];
}
