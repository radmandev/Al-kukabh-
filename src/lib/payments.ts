// Payment methods offered at checkout, driven by Settings.payments.
// ponytail: card gateway = one adapter slot. When a provider is chosen (Tap, Moyasar, Stripe, HyperPay...),
// implement createCheckout() for it and return its hosted-payment URL; the order is already saved as "pending".
import type { Settings } from "./settings";

export function paymentMethods(s: Settings) {
  const m: { id: string; labelAr: string; labelEn: string }[] = [];
  if (s.payments.gateway !== "none") m.push({ id: "card", labelAr: "بطاقة ائتمان / مدى / Apple Pay", labelEn: "Card / Apple Pay" });
  if (s.payments.cod) m.push({ id: "cod", labelAr: "الدفع عند الاستلام", labelEn: "Cash on delivery" });
  if (s.payments.bank) m.push({ id: "bank", labelAr: "تحويل بنكي", labelEn: "Bank transfer" });
  return m;
}

export async function createCheckout(_order: { number: string; total: number; currency: string }, s: Settings): Promise<string | null> {
  if (s.payments.gateway === "none") return null;
  throw new Error(`Payment gateway "${s.payments.gateway}" is not connected yet`);
}

export function shippingCost(subtotal: number, s: Settings) {
  return s.shipping.freeOver > 0 && subtotal >= s.shipping.freeOver ? 0 : s.shipping.flat;
}
