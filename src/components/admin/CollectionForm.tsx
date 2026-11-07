"use client";
import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { saveCollection, deleteCollection } from "@/app/admin/actions";
import { MediaPicker, Thumb, type M } from "./media";
import { ItemPicker } from "./pickers";
import { Field, btn, card, danger, inp } from "./ui";

type Init = { id?: string; nameAr: string; nameEn: string; slug: string; descAr: string; descEn: string; cover: M | null; coverMobile: M | null; published: boolean; productIds: string[] };

export function CollectionForm({ init, products }: { init: Init; products: { id: string; label: string; sub?: string }[] }) {
  const [c, setC] = useState(init);
  const [pick, setPick] = useState<"cover" | "coverMobile" | null>(null);
  const [msg, setMsg] = useState("");
  const [pending, start] = useTransition();
  const router = useRouter();
  const set = <K extends keyof Init>(k: K, v: Init[K]) => setC((x) => ({ ...x, [k]: v }));
  const save = () => start(async () => {
    const r = await saveCollection({ id: c.id, nameAr: c.nameAr, nameEn: c.nameEn, slug: c.slug, descAr: c.descAr, descEn: c.descEn, coverId: c.cover?.id ?? null, coverMobileId: c.coverMobile?.id ?? null, published: c.published, productIds: c.productIds });
    if (r.error) return setMsg(r.error);
    setMsg("Saved");
    if (!c.id) router.replace(`/admin/collections/${r.id}`); else router.refresh();
  });
  const slot = (k: "cover" | "coverMobile", label: string) => (
    <div><p className="mb-2 text-xs font-medium text-stone">{label}</p>
      {c[k] ? <Thumb m={c[k]!} className="aspect-[4/5] w-40 rounded" /> : <div className="grid aspect-[4/5] w-40 place-items-center rounded border border-dashed border-black/20 text-xs text-stone">None</div>}
      <div className="mt-2 flex gap-3 text-sm"><button type="button" className="underline" onClick={() => setPick(k)}>{c[k] ? "Replace" : "Choose / upload"}</button>{c[k] && <button type="button" className="text-red-700" onClick={() => set(k, null)}>Remove</button>}</div>
    </div>
  );
  return (
    <div className="grid gap-6 xl:grid-cols-[1fr_320px]">
      <div className="space-y-6">
        <section className={`${card} space-y-4`}>
          <div className="grid gap-4 md:grid-cols-2">
            <Field label="Name (Arabic)"><input dir="rtl" className={inp} value={c.nameAr} onChange={(e) => set("nameAr", e.target.value)} /></Field>
            <Field label="Name (English)"><input className={inp} value={c.nameEn} onChange={(e) => set("nameEn", e.target.value)} /></Field>
            <Field label="Description (Arabic)"><textarea rows={3} dir="rtl" className={inp} value={c.descAr} onChange={(e) => set("descAr", e.target.value)} /></Field>
            <Field label="Description (English)"><textarea rows={3} className={inp} value={c.descEn} onChange={(e) => set("descEn", e.target.value)} /></Field>
          </div>
          <Field label="URL slug" hint="Leave empty to generate from English name"><input className={inp} value={c.slug} onChange={(e) => set("slug", e.target.value)} /></Field>
        </section>
        <section className={card}><h2 className="mb-4 font-medium">Cover (image or video)</h2><div className="flex flex-wrap gap-8">{slot("cover", "Desktop")}{slot("coverMobile", "Mobile (optional)")}</div></section>
        <section className={card}><h2 className="mb-4 font-medium">Products in this collection</h2><ItemPicker items={products} value={c.productIds} onChange={(v) => set("productIds", v)} /></section>
      </div>
      <div className="space-y-4 xl:sticky xl:top-6 xl:self-start">
        <section className={card}>
          <label className="flex items-center gap-2 text-sm"><input type="checkbox" className="accent-ink" checked={c.published} onChange={(e) => set("published", e.target.checked)} />Published</label>
          <div className="mt-4 flex items-center gap-3"><button disabled={pending} onClick={save} className={btn}>{pending ? "Saving…" : "Save collection"}</button><span className="text-sm text-stone">{msg}</span></div>
        </section>
        {c.id && <button className={danger} onClick={() => confirm("Delete collection? Products are kept.") && start(async () => { await deleteCollection(c.id!); router.push("/admin/collections"); })}>Delete collection</button>}
      </div>
      <MediaPicker open={!!pick} onClose={() => setPick(null)} onPick={([m]) => pick && set(pick, m)} />
    </div>
  );
}
