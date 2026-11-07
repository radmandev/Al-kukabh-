"use client";
import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { saveSection, deleteSection } from "@/app/admin/actions";
import type { Field as F } from "@/lib/sections";
import { MediaField, type Ref } from "./media";
import { ItemPicker } from "./pickers";
import { Field, btn, btn2, card, danger, inp } from "./ui";

type Data = Record<string, unknown>;
type Opts = { products: { id: string; label: string; sub?: string }[]; collections: { id: string; label: string }[] };

function Fields({ fields, value, onChange, opts }: { fields: F[]; value: Data; onChange: (d: Data) => void; opts: Opts }) {
  const set = (k: string, v: unknown) => onChange({ ...value, [k]: v });
  return (
    <div className="grid gap-4 md:grid-cols-2">
      {fields.map((f) => {
        const v = value[f.key];
        const wide = ["textarea", "media", "list", "products", "collections"].includes(f.kind);
        const rtl = f.key.endsWith("Ar") ? "rtl" : undefined;
        let el: React.ReactNode;
        switch (f.kind) {
          case "text": case "link": el = <input dir={rtl} className={inp} value={String(v ?? "")} placeholder={f.kind === "link" ? "/ar/shop?tag=new" : ""} onChange={(e) => set(f.key, e.target.value)} />; break;
          case "textarea": el = <textarea dir={rtl} rows={3} className={inp} value={String(v ?? "")} onChange={(e) => set(f.key, e.target.value)} />; break;
          case "number": el = <input type="number" min={1} className={inp} value={String(v ?? "")} onChange={(e) => set(f.key, e.target.value ? +e.target.value : undefined)} />; break;
          case "bool": el = <input type="checkbox" checked={!!v} onChange={(e) => set(f.key, e.target.checked)} />; break;
          case "select": el = <select className={inp} value={String(v ?? f.options[0])} onChange={(e) => set(f.key, e.target.value)}>{f.options.map((o) => <option key={o}>{o}</option>)}</select>; break;
          case "media": el = <MediaField value={v as Ref | undefined} onChange={(m) => set(f.key, m)} />; break;
          case "products": el = <ItemPicker items={opts.products} value={(v as string[]) ?? []} onChange={(x) => set(f.key, x)} />; break;
          case "collections": el = <ItemPicker items={opts.collections} value={(v as string[]) ?? []} onChange={(x) => set(f.key, x)} />; break;
          case "list": {
            const arr = (v as Data[]) ?? [];
            const up = (a: Data[]) => set(f.key, a);
            el = (
              <div className="space-y-3">
                {arr.map((item, i) => (
                  <div key={i} className="rounded border border-black/10 bg-[#faf9f6] p-4">
                    <div className="mb-3 flex items-center gap-3 text-xs text-stone">
                      <span className="font-medium">#{i + 1}</span>
                      {i > 0 && <button type="button" onClick={() => { const a = [...arr]; [a[i - 1], a[i]] = [a[i], a[i - 1]]; up(a); }}>↑ Move up</button>}
                      {i < arr.length - 1 && <button type="button" onClick={() => { const a = [...arr]; [a[i + 1], a[i]] = [a[i], a[i + 1]]; up(a); }}>↓ Move down</button>}
                      <button type="button" className="ms-auto text-red-700" onClick={() => up(arr.filter((_, j) => j !== i))}>Remove</button>
                    </div>
                    <Fields fields={f.fields} value={item} onChange={(d) => up(arr.map((x, j) => (j === i ? d : x)))} opts={opts} />
                  </div>
                ))}
                <button type="button" className={btn2} onClick={() => up([...arr, {}])}>+ Add {f.label.toLowerCase().replace(/s$/, "")}</button>
              </div>
            );
          }
        }
        return <Field key={f.key} label={f.label} className={wide ? "md:col-span-2" : ""}>{el}</Field>;
      })}
    </div>
  );
}

export function SectionEditor({ id, label, fields, init, published, opts }: { id: string; label: string; fields: F[]; init: Data; published: boolean; opts: Opts }) {
  const [d, setD] = useState(init);
  const [pub, setPub] = useState(published);
  const [msg, setMsg] = useState("");
  const [pending, start] = useTransition();
  const router = useRouter();
  return (
    <div className="space-y-6">
      <div className={`${card} sticky top-0 z-10 flex flex-wrap items-center gap-4`}>
        <span className="font-medium">{label}</span>
        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={pub} onChange={(e) => setPub(e.target.checked)} className="accent-ink" />Published</label>
        <button className={btn} disabled={pending} onClick={() => start(async () => { await saveSection(id, d, pub); setMsg("Saved ✓"); router.refresh(); })}>{pending ? "Saving…" : "Save section"}</button>
        <span className="text-sm text-emerald-700">{msg}</span>
        <button className={`${danger} ms-auto`} onClick={() => confirm("Delete this section?") && start(async () => { await deleteSection(id); router.push("/admin/homepage"); })}>Delete</button>
      </div>
      <div className={card}><Fields fields={fields} value={d} onChange={(x) => { setD(x); setMsg(""); }} opts={opts} /></div>
    </div>
  );
}
