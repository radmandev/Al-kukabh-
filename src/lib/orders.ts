import "server-only";
import { db } from "./db";

export async function computeDiscount(code: string | undefined, subtotal: number) {
  if (!code) return { amount: 0, code: null as string | null };
  const d = await db.discount.findUnique({ where: { code: code.trim().toUpperCase() } });
  const valid = d && d.active && subtotal >= d.minTotal && (!d.expiresAt || d.expiresAt > new Date()) && (d.maxUses == null || d.used < d.maxUses);
  if (!valid) return null;
  const amount = d.type === "percent" ? (subtotal * d.value) / 100 : d.value;
  return { amount: Math.min(Math.round(amount * 100) / 100, subtotal), code: d.code };
}

export const orderNumber = () => "KW" + Date.now().toString(36).toUpperCase().slice(-6) + Math.floor(Math.random() * 90 + 10);
