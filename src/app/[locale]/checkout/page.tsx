import type { Metadata } from "next";
import { dict, type Locale } from "@/lib/i18n";
import { getSettings } from "@/lib/settings";
import { paymentMethods } from "@/lib/payments";
import { currentUser } from "@/lib/auth";
import { CheckoutForm } from "@/components/store/CheckoutForm";

export const metadata: Metadata = { title: "Checkout", robots: { index: false } };

export default async function Checkout({ params }: { params: Promise<{ locale: Locale }> }) {
  const { locale: l } = await params;
  const [s, u] = await Promise.all([getSettings(), currentUser()]);
  return (
    <CheckoutForm l={l} t={dict(l)} currency={s.currency} methods={paymentMethods(s)}
      shipping={{ flat: s.shipping.flat, freeOver: s.shipping.freeOver }} countries={s.shipping.countries.split(",").map((c) => c.trim()).filter(Boolean)}
      bankDetails={l === "ar" ? s.payments.bankDetailsAr : s.payments.bankDetailsEn}
      user={u ? { name: u.name, email: u.email, phone: u.phone ?? "" } : null} />
  );
}
