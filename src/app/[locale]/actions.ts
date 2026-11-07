"use server";
import { z } from "zod";
import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getSettings } from "@/lib/settings";
import { currentUser, startSession, endSession, hashPassword, checkPassword } from "@/lib/auth";
import { computeDiscount, orderNumber } from "@/lib/orders";
import { createCheckout, paymentMethods, shippingCost } from "@/lib/payments";
import { json } from "@/lib/format";

export async function subscribe(_: unknown, f: FormData) {
  const email = z.email().safeParse(f.get("email"));
  if (!email.success) return "invalid";
  await db.subscriber.upsert({ where: { email: email.data.toLowerCase() }, create: { email: email.data.toLowerCase() }, update: {} });
  return "ok";
}

export async function login(_: unknown, f: FormData) {
  const email = String(f.get("email") ?? "").toLowerCase().trim();
  const u = await db.user.findUnique({ where: { email } });
  if (!u || !(await checkPassword(String(f.get("password") ?? ""), u.passwordHash))) return "invalidLogin";
  await startSession(u.id);
  redirect(String(f.get("next") || `/${f.get("locale")}/account`));
}

const Reg = z.object({ name: z.string().trim().min(2).max(80), email: z.email().toLowerCase(), password: z.string().min(8).max(100), phone: z.string().max(30).optional() });
export async function register(_: unknown, f: FormData) {
  const p = Reg.safeParse(Object.fromEntries(f));
  if (!p.success) return "required";
  if (await db.user.findUnique({ where: { email: p.data.email } })) return "emailTaken";
  const u = await db.user.create({ data: { name: p.data.name, email: p.data.email, phone: p.data.phone, passwordHash: await hashPassword(p.data.password) } });
  await startSession(u.id);
  redirect(`/${f.get("locale")}/account`);
}

export async function logout(f: FormData) {
  await endSession();
  redirect(`/${f.get("locale")}`);
}

export async function checkDiscount(code: string, subtotal: number) {
  return computeDiscount(code, subtotal);
}

const Checkout = z.object({
  name: z.string().trim().min(2).max(100), email: z.email(), phone: z.string().trim().min(6).max(30),
  country: z.string().trim().min(2).max(60), city: z.string().trim().min(2).max(80), address: z.string().trim().min(4).max(300),
  notes: z.string().max(500).default(""), payment: z.string(), code: z.string().max(40).optional(),
  lines: z.array(z.object({ productId: z.string(), size: z.string().max(20), color: z.string().max(40), qty: z.number().int().min(1).max(10) })).min(1).max(30),
});

export async function placeOrder(input: z.input<typeof Checkout>, locale: string): Promise<{ error: string } | { url: string }> {
  const p = Checkout.safeParse(input);
  if (!p.success) return { error: "required" };
  const d = p.data;
  const s = await getSettings();
  if (!paymentMethods(s).some((m) => m.id === d.payment)) return { error: "required" };

  const products = await db.product.findMany({ where: { id: { in: d.lines.map((x) => x.productId) }, published: true }, include: { media: { include: { media: true }, orderBy: { sort: "asc" }, take: 1 } } });
  const items: { productId: string; sku: string; slug: string; nameAr: string; nameEn: string; image?: string; size: string; color: string; qty: number; price: number }[] = [];
  for (const x of d.lines) {
    const pr = products.find((q) => q.id === x.productId);
    if (!pr || pr.availability === "out_of_stock") return { error: "outOfStock" };
    const sizes = json<string[]>(pr.sizes, []);
    if (sizes.length && !sizes.includes(x.size)) return { error: "selectSize" };
    if (pr.availability === "in_stock" && pr.stock < x.qty) return { error: "outOfStock" };
    items.push({ productId: pr.id, sku: pr.sku, slug: pr.slug, nameAr: pr.nameAr, nameEn: pr.nameEn, image: pr.media[0]?.media.url, size: x.size, color: x.color, qty: x.qty, price: pr.salePrice ?? pr.price });
  }
  const subtotal = items.reduce((a, x) => a + x.price * x.qty, 0);
  const disc = await computeDiscount(d.code, subtotal);
  if (disc === null) return { error: "invalidCode" };
  const shipping = shippingCost(subtotal - disc.amount, s);
  const user = await currentUser();
  const number = orderNumber();

  try {
    await db.$transaction(async (tx) => {
      for (const it of items) {
        const pr = products.find((q) => q.id === it.productId)!;
        if (pr.availability !== "in_stock") continue;
        const r = await tx.product.updateMany({ where: { id: it.productId, stock: { gte: it.qty } }, data: { stock: { decrement: it.qty } } });
        if (r.count === 0) throw new Error("stock");
      }
      if (disc.code) await tx.discount.update({ where: { code: disc.code }, data: { used: { increment: 1 } } });
      await tx.order.create({
        data: {
          number, userId: user?.id, email: d.email.toLowerCase(), name: d.name, phone: d.phone, country: d.country, city: d.city,
          address: d.address, notes: d.notes, items: JSON.stringify(items), subtotal, discount: disc.amount, discountCode: disc.code,
          shipping, total: subtotal - disc.amount + shipping, currency: s.currency, paymentMethod: d.payment,
          history: JSON.stringify([{ at: new Date(), note: "Order placed" }]),
        },
      });
    });
  } catch {
    return { error: "outOfStock" };
  }
  if (d.payment === "card") {
    const url = await createCheckout({ number, total: subtotal - disc.amount + shipping, currency: s.currency }, s);
    if (url) return { url };
  }
  return { url: `/${locale}/order/${number}?e=${encodeURIComponent(d.email.toLowerCase())}` };
}
