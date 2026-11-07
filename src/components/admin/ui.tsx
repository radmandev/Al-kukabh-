// Small shared admin primitives (no UI kit needed).
export const card = "rounded-md border border-black/10 bg-white p-5 md:p-6";
export const inp = "w-full rounded border border-black/15 bg-white px-3 py-2 text-sm outline-none focus:border-ink";
export const btn = "inline-flex items-center gap-2 rounded bg-ink px-4 py-2 text-sm text-ivory hover:bg-onyx disabled:opacity-50";
export const btn2 = "inline-flex items-center gap-2 rounded border border-black/15 bg-white px-3 py-1.5 text-sm hover:border-ink disabled:opacity-50";
export const danger = "text-sm text-red-700 hover:underline";

export function PageHead({ title, children }: { title: string; children?: React.ReactNode }) {
  return <div className="mb-8 flex flex-wrap items-center justify-between gap-4"><h1 className="display text-3xl md:text-4xl">{title}</h1><div className="flex flex-wrap gap-2">{children}</div></div>;
}
export function Field({ label, children, hint, className = "" }: { label: string; children: React.ReactNode; hint?: string; className?: string }) {
  return <label className={`block ${className}`}><span className="mb-1 block text-xs font-medium text-stone">{label}</span>{children}{hint && <span className="mt-1 block text-xs text-stone/70">{hint}</span>}</label>;
}
export function Badge({ children, tone = "neutral" }: { children: React.ReactNode; tone?: "neutral" | "good" | "warn" | "bad" | "gold" }) {
  const c = { neutral: "bg-black/5 text-stone", good: "bg-emerald-50 text-emerald-800", warn: "bg-amber-50 text-amber-800", bad: "bg-red-50 text-red-800", gold: "bg-ink text-gold-soft" }[tone];
  return <span className={`inline-block rounded px-2 py-0.5 text-[.7rem] ${c}`}>{children}</span>;
}
export const toneFor = (s: string) => (["paid", "completed", "delivered", "confirmed"].includes(s) ? "good" : ["cancelled", "failed", "refunded", "returned"].includes(s) ? "bad" : ["pending", "unfulfilled"].includes(s) ? "warn" : "neutral");
