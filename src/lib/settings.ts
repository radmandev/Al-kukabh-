import "server-only";
import { cache } from "react";
import { db } from "./db";

export const defaultSettings = {
  brand: {
    nameAr: "الكوكبة", nameEn: "Al Kawkaba",
    taglineAr: "للعبايات", taglineEn: "Abayas",
    aboutAr: "دار عبايات فاخرة تحتفي بالأناقة الهادئة والحِرفة المتقنة، حيث تُصاغ كل قطعة بعناية لتليق بكِ.",
    aboutEn: "A house of luxury abayas celebrating quiet elegance and masterful craft — every piece composed with care, made for you.",
    logoUrl: "/brand/logo.jpg",
  },
  contact: { email: "care@alkawkaba.com", phone: "", whatsapp: "", addressAr: "", addressEn: "" },
  social: { instagram: "", tiktok: "", snapchat: "", x: "" },
  currency: "SAR",
  shipping: { flat: 30, freeOver: 500, countries: "SA,AE,KW,QA,BH,OM" },
  payments: {
    cod: true, bank: true, bankDetailsAr: "", bankDetailsEn: "",
    // ponytail: card gateway is a setting + adapter slot in src/lib/payments.ts; wire the provider when chosen.
    gateway: "none",
  },
  policies: { shippingAr: "", shippingEn: "", returnsAr: "", returnsEn: "", privacyAr: "", privacyEn: "", termsAr: "", termsEn: "" },
  seo: { titleAr: "الكوكبة للعبايات", titleEn: "Al Kawkaba Abayas", descAr: "عبايات فاخرة مصممة بإتقان.", descEn: "Luxury abayas, masterfully crafted.", ogImage: "" },
};
export type Settings = typeof defaultSettings;
export type SettingsKey = keyof Settings;

export const getSettings = cache(async (): Promise<Settings> => {
  const rows = await db.setting.findMany();
  const s = structuredClone(defaultSettings) as Record<string, unknown>;
  for (const r of rows) {
    if (!(r.key in s)) continue;
    const v = JSON.parse(r.value);
    const d = s[r.key];
    s[r.key] = d && typeof d === "object" ? { ...d, ...v } : v;
  }
  return s as Settings;
});

export const saveSetting = (key: SettingsKey, value: unknown) =>
  db.setting.upsert({ where: { key }, create: { key, value: JSON.stringify(value) }, update: { value: JSON.stringify(value) } });
