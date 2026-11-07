"use server";
import { z } from "zod";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { db } from "@/lib/db";
import { requireStaff, startSession, endSession, checkPassword, hashPassword } from "@/lib/auth";
import { deleteMedia as removeMediaFile } from "@/lib/storage";
import { saveSetting, defaultSettings, type SettingsKey } from "@/lib/settings";
import { SECTION_TYPES } from "@/lib/sections";
import { slugify, json } from "@/lib/format";

const done = () => revalidatePath("/", "layout");

/* ---------- auth ---------- */
export async function adminLogin(_: unknown, f: FormData) {
  const u = await db.user.findUnique({ where: { email: String(f.get("email") ?? "").toLowerCase().trim() } });
  if (!u || u.role === "CUSTOMER" || !(await checkPassword(String(f.get("password") ?? "").replace(/[٠-٩]/g, (c) => String("٠١٢٣٤٥٦٧٨٩".indexOf(c))), u.passwordHash))) return "Invalid email or password";
  await startSession(u.id);
  redirect("/admin");
}
export async function adminLogout() { await endSession(); redirect("/admin/login"); }

/* ---------- products ---------- */
const Color = z.object({ nameAr: z.string(), nameEn: z.string(), hex: z.string().regex(/^#[0-9a-fA-F]{6}$/) });
const ProductIn = z.object({
  id: z.string().optional(),
  nameAr: z.string().trim().min(1), nameEn: z.string().trim().min(1),
  slug: z.string().trim().optional(), sku: z.string().trim().min(1),
  descAr: z.string(), descEn: z.string(), fabricAr: z.string(), fabricEn: z.string(), detailsAr: z.string(), detailsEn: z.string(),
  careAr: z.string(), careEn: z.string(), deliveryAr: z.string(), deliveryEn: z.string(),
  price: z.number().nonnegative(), salePrice: z.number().nonnegative().nullable(),
  stock: z.number().int().nonnegative(), availability: z.enum(["in_stock", "made_to_order", "out_of_stock"]),
  sizes: z.array(z.string().trim().min(1)), colors: z.array(Color),
  isNew: z.boolean(), isFeatured: z.boolean(), isLimited: z.boolean(), isReady: z.boolean(), published: z.boolean(),
  seoTitle: z.string(), seoDesc: z.string(),
  categoryId: z.string().nullable(), collectionId: z.string().nullable(),
  media: z.array(z.string()), // ordered media ids, first = primary
});
export type ProductInput = z.infer<typeof ProductIn>;

export async function saveProduct(input: ProductInput): Promise<{ error?: string; id?: string }> {
  await requireStaff();
  const p = ProductIn.safeParse(input);
  if (!p.success) return { error: p.error.issues.map((i) => `${i.path.join(".")}: ${i.message}`).join("; ") };
  const { id, media, sizes, colors, ...d } = p.data;
  if (d.salePrice != null && d.salePrice >= d.price) return { error: "Discount price must be lower than the price" };
  const slug = slugify(d.slug || d.nameEn);
  const data = { ...d, slug, sizes: JSON.stringify(sizes), colors: JSON.stringify(colors) };
  try {
    const saved = await db.$transaction(async (tx) => {
      const pr = id ? await tx.product.update({ where: { id }, data }) : await tx.product.create({ data });
      await tx.productMedia.deleteMany({ where: { productId: pr.id } });
      if (media.length) await tx.productMedia.createMany({ data: media.map((mediaId, sort) => ({ productId: pr.id, mediaId, sort })) });
      return pr;
    });
    done();
    return { id: saved.id };
  } catch (e) {
    return { error: String(e).includes("Unique") ? "Slug or SKU already used by another product" : "Could not save product" };
  }
}
export async function deleteProduct(id: string) { await requireStaff(); await db.product.delete({ where: { id } }); done(); }
export async function toggleProduct(id: string, published: boolean) { await requireStaff(); await db.product.update({ where: { id }, data: { published } }); done(); }

/* ---------- collections & categories ---------- */
const ColIn = z.object({
  id: z.string().optional(), nameAr: z.string().trim().min(1), nameEn: z.string().trim().min(1), slug: z.string().optional(),
  descAr: z.string(), descEn: z.string(), coverId: z.string().nullable(), coverMobileId: z.string().nullable(), published: z.boolean(),
  productIds: z.array(z.string()),
});
export async function saveCollection(input: z.infer<typeof ColIn>): Promise<{ error?: string; id?: string }> {
  await requireStaff();
  const p = ColIn.safeParse(input);
  if (!p.success) return { error: "Arabic and English names are required" };
  const { id, productIds, ...d } = p.data;
  const data = { ...d, slug: slugify(d.slug || d.nameEn) };
  try {
    const c = id ? await db.collection.update({ where: { id }, data }) : await db.collection.create({ data: { ...data, sort: await db.collection.count() } });
    await db.$transaction([
      db.product.updateMany({ where: { collectionId: c.id, id: { notIn: productIds } }, data: { collectionId: null } }),
      db.product.updateMany({ where: { id: { in: productIds } }, data: { collectionId: c.id } }),
    ]);
    done();
    return { id: c.id };
  } catch { return { error: "Slug already used" }; }
}
export async function deleteCollection(id: string) { await requireStaff(); await db.collection.delete({ where: { id } }); done(); }

export async function saveCategory(f: FormData) {
  await requireStaff();
  const nameAr = String(f.get("nameAr") ?? "").trim(), nameEn = String(f.get("nameEn") ?? "").trim();
  if (!nameAr || !nameEn) return;
  const id = String(f.get("id") ?? "");
  const data = { nameAr, nameEn, slug: slugify(String(f.get("slug") || nameEn)) };
  if (id) await db.category.update({ where: { id }, data }); else await db.category.create({ data: { ...data, sort: await db.category.count() } });
  done();
}
export async function deleteCategory(id: string) { await requireStaff(); await db.category.delete({ where: { id } }); done(); }

/** generic reorder for collections, categories and sections */
export async function reorder(model: "collection" | "category" | "section", ids: string[]) {
  await requireStaff();
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  await db.$transaction(ids.map((id, sort) => (db[model] as any).update({ where: { id }, data: { sort } })));
  done();
}

/* ---------- homepage sections ---------- */
export async function addSection(type: string) {
  await requireStaff();
  if (!SECTION_TYPES[type]) return;
  const s = await db.section.create({ data: { type, sort: await db.section.count(), published: false } });
  redirect(`/admin/homepage/${s.id}`);
}
export async function saveSection(id: string, data: unknown, published: boolean) {
  await requireStaff();
  await db.section.update({ where: { id }, data: { data: JSON.stringify(data), published } });
  done();
}
export async function toggleSection(id: string, published: boolean) { await requireStaff(); await db.section.update({ where: { id }, data: { published } }); done(); }
export async function deleteSection(id: string) { await requireStaff(); await db.section.delete({ where: { id } }); done(); }
export async function duplicateSection(id: string) {
  await requireStaff();
  const s = await db.section.findUniqueOrThrow({ where: { id } });
  await db.section.create({ data: { type: s.type, data: s.data, published: false, sort: s.sort + 1 } });
  done();
}

/* ---------- media ---------- */
export async function updateMedia(id: string, d: { altAr?: string; altEn?: string; posterId?: string | null }) {
  await requireStaff();
  await db.media.update({ where: { id }, data: d });
  done();
}
export async function deleteMedia(id: string) { await requireStaff(); await removeMediaFile(id); done(); }

/* ---------- orders ---------- */
const OrderUp = z.object({ status: z.string(), paymentStatus: z.string(), shipStatus: z.string(), tracking: z.string().max(200), note: z.string().max(500) });
export async function updateOrder(id: string, input: z.infer<typeof OrderUp>) {
  const u = await requireStaff();
  const d = OrderUp.parse(input);
  const o = await db.order.findUniqueOrThrow({ where: { id } });
  const changes = (["status", "paymentStatus", "shipStatus", "tracking"] as const).filter((k) => o[k] !== d[k]).map((k) => `${k}: ${o[k] || "—"} → ${d[k] || "—"}`);
  const note = [...changes, d.note].filter(Boolean).join(" · ");
  const history = json<object[]>(o.history, []);
  if (note) history.push({ at: new Date(), note, by: u.name });
  // restock when an order is cancelled
  if (d.status === "cancelled" && o.status !== "cancelled") {
    for (const it of json<{ productId: string; qty: number }[]>(o.items, []))
      await db.product.updateMany({ where: { id: it.productId, availability: "in_stock" }, data: { stock: { increment: it.qty } } });
  }
  await db.order.update({ where: { id }, data: { status: d.status, paymentStatus: d.paymentStatus, shipStatus: d.shipStatus, tracking: d.tracking, history: JSON.stringify(history) } });
  revalidatePath(`/admin/orders/${id}`);
}

/* ---------- discounts ---------- */
export async function saveDiscount(f: FormData) {
  await requireStaff();
  const d = z.object({
    code: z.string().trim().min(2).toUpperCase(), type: z.enum(["percent", "fixed"]), value: z.coerce.number().positive(),
    minTotal: z.coerce.number().min(0).default(0), maxUses: z.string().optional(), expiresAt: z.string().optional(),
  }).parse(Object.fromEntries(f));
  await db.discount.upsert({
    where: { code: d.code },
    create: { code: d.code, type: d.type, value: d.value, minTotal: d.minTotal, maxUses: d.maxUses ? +d.maxUses : null, expiresAt: d.expiresAt ? new Date(d.expiresAt) : null },
    update: { type: d.type, value: d.value, minTotal: d.minTotal, maxUses: d.maxUses ? +d.maxUses : null, expiresAt: d.expiresAt ? new Date(d.expiresAt) : null },
  });
  revalidatePath("/admin/discounts");
}
export async function toggleDiscount(id: string, active: boolean) { await requireStaff(); await db.discount.update({ where: { id }, data: { active } }); revalidatePath("/admin/discounts"); }
export async function deleteDiscount(id: string) { await requireStaff(); await db.discount.delete({ where: { id } }); revalidatePath("/admin/discounts"); }

/* ---------- settings & staff (ADMIN only) ---------- */
export async function saveSettings(key: SettingsKey, value: unknown) {
  await requireStaff(["ADMIN"]);
  if (!(key in defaultSettings)) return;
  await saveSetting(key, value);
  done();
}
export async function saveStaff(f: FormData) {
  await requireStaff(["ADMIN"]);
  const d = z.object({ name: z.string().trim().min(2), email: z.email().toLowerCase(), password: z.string().min(8), role: z.enum(["ADMIN", "EDITOR"]) }).safeParse(Object.fromEntries(f));
  if (!d.success) return;
  const passwordHash = await hashPassword(d.data.password);
  await db.user.upsert({ where: { email: d.data.email }, create: { name: d.data.name, email: d.data.email, role: d.data.role, passwordHash }, update: { name: d.data.name, role: d.data.role, passwordHash } });
  revalidatePath("/admin/staff");
}
export async function setRole(id: string, role: string) {
  const me = await requireStaff(["ADMIN"]);
  if (id === me.id || !["ADMIN", "EDITOR", "CUSTOMER"].includes(role)) return;
  await db.user.update({ where: { id }, data: { role } });
  revalidatePath("/admin/staff");
}
