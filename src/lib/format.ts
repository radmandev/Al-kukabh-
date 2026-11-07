import type { Locale } from "./i18n";

export function money(v: number, currency: string, l: Locale) {
  return new Intl.NumberFormat(l === "ar" ? "ar-SA-u-nu-latn" : "en", {
    style: "currency", currency, maximumFractionDigits: v % 1 ? 2 : 0,
  }).format(v);
}

export const json = <T,>(s: string | null | undefined, fallback: T): T => {
  try { return s ? (JSON.parse(s) as T) : fallback; } catch { return fallback; }
};

export const slugify = (s: string) =>
  s.toLowerCase().trim().replace(/[^\p{L}\p{N}]+/gu, "-").replace(/^-|-$/g, "");
