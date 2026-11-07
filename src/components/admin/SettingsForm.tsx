"use client";
import { useState, useTransition } from "react";
import { saveSettings } from "@/app/admin/actions";
import type { Settings, SettingsKey } from "@/lib/settings";
import { MediaPicker } from "./media";
import { Field, btn, card, inp } from "./ui";

const TABS: [SettingsKey, string, string][] = [
  ["brand", "Brand & logo", ""], ["contact", "Contact", ""], ["social", "Social media", "Full profile URLs, e.g. https://instagram.com/yourbrand"],
  ["currency", "Currency", "ISO code shown on all prices, e.g. SAR, AED, KWD, YER, USD"],
  ["shipping", "Shipping", "Flat rate per order; free above the threshold (0 = never free). Countries: comma-separated ISO codes."],
  ["payments", "Payments", "Card gateway activates once a provider is connected (see README)."], ["policies", "Store policies", ""], ["seo", "SEO", ""],
];
const human = (k: string) => k.replace(/([A-Z])/g, " $1").replace(/^./, (c) => c.toUpperCase()).replace(/ Ar$/, " (Arabic)").replace(/ En$/, " (English)");
const LONG = /about|policy|shipping[AE]|returns|privacy|terms|bankDetails|desc[AE]/i;

export function SettingsForm({ init }: { init: Settings }) {
  const [tab, setTab] = useState<SettingsKey>("brand");
  const [s, setS] = useState(init);
  const [msg, setMsg] = useState("");
  const [pick, setPick] = useState<string | null>(null);
  const [pending, start] = useTransition();
  const v = s[tab];
  const set = (k: string, x: unknown) => { setMsg(""); setS({ ...s, [tab]: typeof v === "object" ? { ...(v as object), [k]: x } : x }); };
  const input = (k: string, val: unknown) => {
    if (typeof val === "boolean") return <label key={k} className="flex items-center gap-2 text-sm"><input type="checkbox" checked={val} onChange={(e) => set(k, e.target.checked)} />{human(k)}</label>;
    if (k === "logoUrl" || k === "ogImage") return (
      <Field key={k} label={human(k)} className="md:col-span-2">
        <div className="flex items-center gap-4">{val ? <img src={String(val)} alt="" className="h-20 w-20 rounded-full object-cover" /> : null}<button type="button" className="underline" onClick={() => setPick(k)}>Choose / upload</button></div>
      </Field>
    );
    if (k === "gateway") return <Field key={k} label="Card gateway"><select className={inp} value={String(val)} onChange={(e) => set(k, e.target.value)}>{["none", "tap", "moyasar", "stripe", "hyperpay"].map((g) => <option key={g}>{g}</option>)}</select></Field>;
    const long = LONG.test(k);
    const p = { className: inp, dir: k.endsWith("Ar") ? "rtl" : undefined, value: String(val ?? "") };
    return (
      <Field key={k} label={tab === "currency" ? TABS.find((t) => t[0] === tab)![1] : human(k)} className={long ? "md:col-span-2" : ""}>
        {long ? <textarea rows={6} {...p} onChange={(e) => set(k, e.target.value)} />
          : <input {...p} type={typeof val === "number" ? "number" : "text"} onChange={(e) => set(k, typeof val === "number" ? +e.target.value : e.target.value)} />}
      </Field>
    );
  };
  return (
    <div className="grid gap-6 lg:grid-cols-[200px_1fr]">
      <nav className="flex gap-1 overflow-x-auto lg:flex-col">
        {TABS.map(([k, l]) => <button key={k} onClick={() => setTab(k)} className={`whitespace-nowrap rounded px-3 py-2 text-start text-sm ${tab === k ? "bg-ink text-ivory" : "hover:bg-black/5"}`}>{l}</button>)}
      </nav>
      <section className={card}>
        {TABS.find((t) => t[0] === tab)![2] && <p className="mb-4 text-sm text-stone">{TABS.find((t) => t[0] === tab)![2]}</p>}
        <div className="grid gap-4 md:grid-cols-2">
          {typeof v === "object" ? Object.entries(v as object).map(([k, x]) => input(k, x)) : input(tab, v)}
        </div>
        <div className="mt-6 flex items-center gap-3">
          <button className={btn} disabled={pending} onClick={() => start(async () => { await saveSettings(tab, tab === "currency" ? String(v).toUpperCase().trim() : v); setMsg("Saved ✓"); })}>{pending ? "Saving…" : "Save"}</button>
          <span className="text-sm text-emerald-700">{msg}</span>
        </div>
      </section>
      <MediaPicker open={!!pick} kind="image" onClose={() => setPick(null)} onPick={([m]) => pick && set(pick, m.url)} />
    </div>
  );
}
