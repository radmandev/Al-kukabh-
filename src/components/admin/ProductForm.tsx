"use client";
import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { saveProduct, deleteProduct, type ProductInput } from "@/app/admin/actions";
import { MediaManager, type M } from "./media";
import { Field, btn, btn2, card, danger, inp } from "./ui";

type Opt = { id: string; nameEn: string };
type Init = Omit<ProductInput, "media"> & { media: M[] };

export function ProductForm({ init, categories, collections }: { init: Init; categories: Opt[]; collections: Opt[] }) {
  const [p, setP] = useState(init);
  const [msg, setMsg] = useState<{ ok?: boolean; text: string } | null>(null);
  const [pending, start] = useTransition();
  const router = useRouter();
  const set = <K extends keyof Init>(k: K, v: Init[K]) => setP((x) => ({ ...x, [k]: v }));
  const text = (k: keyof Init, label: string, o: { area?: boolean; rtl?: boolean; hint?: string } = {}) => (
    <Field label={label} hint={o.hint}>
      {o.area
        ? <textarea rows={4} dir={o.rtl ? "rtl" : undefined} className={inp} value={String(p[k] ?? "")} onChange={(e) => set(k, e.target.value as never)} />
        : <input dir={o.rtl ? "rtl" : undefined} className={inp} value={String(p[k] ?? "")} onChange={(e) => set(k, e.target.value as never)} />}
    </Field>
  );
  const pair = (k: string, label: string, area?: boolean) => (
    <div className="grid gap-4 md:grid-cols-2">{text(`${k}Ar` as keyof Init, `${label} (Arabic)`, { area, rtl: true })}{text(`${k}En` as keyof Init, `${label} (English)`, { area })}</div>
  );
  const flag = (k: "isNew" | "isFeatured" | "isLimited" | "isReady" | "published", label: string) => (
    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={p[k]} onChange={(e) => set(k, e.target.checked)} className="accent-ink" />{label}</label>
  );
  const save = () => start(async () => {
    const r = await saveProduct({ ...p, media: p.media.map((m) => m.id) });
    if (r.error) return setMsg({ text: r.error });
    setMsg({ ok: true, text: "Saved" });
    if (!init.id) router.replace(`/admin/products/${r.id}`); else router.refresh();
  });

  return (
    <div className="grid gap-6 xl:grid-cols-[1fr_340px]">
      <div className="space-y-6">
        <section className={card}><h2 className="mb-4 font-medium">Basics</h2>
          <div className="space-y-4">{pair("name", "Name")}{pair("desc", "Description", true)}</div></section>
        <section className={card}><h2 className="mb-4 font-medium">Images & videos</h2><MediaManager items={p.media} onChange={(m) => set("media", m)} /></section>
        <section className={card}><h2 className="mb-4 font-medium">Product details</h2>
          <div className="space-y-4">{pair("fabric", "Fabric", true)}{pair("details", "Details", true)}{pair("care", "Care instructions", true)}{pair("delivery", "Delivery info", true)}</div></section>
        <section className={card}><h2 className="mb-4 font-medium">Sizes & colours</h2>
          <Field label="Sizes" hint="Comma separated, e.g. 50, 52, 54, 56, 58 or S, M, L">
            <input className={inp} defaultValue={p.sizes.join(", ")} onBlur={(e) => set("sizes", e.target.value.split(",").map((x) => x.trim()).filter(Boolean))} />
          </Field>
          <div className="mt-4 space-y-2">
            <span className="text-xs font-medium text-stone">Colours</span>
            {p.colors.map((c, i) => (
              <div key={i} className="flex items-center gap-2">
                <input type="color" value={c.hex} onChange={(e) => set("colors", p.colors.map((x, j) => (j === i ? { ...x, hex: e.target.value } : x)))} className="h-9 w-10 rounded border border-black/15" aria-label="Colour" />
                <input className={inp} dir="rtl" placeholder="الاسم" value={c.nameAr} onChange={(e) => set("colors", p.colors.map((x, j) => (j === i ? { ...x, nameAr: e.target.value } : x)))} />
                <input className={inp} placeholder="Name" value={c.nameEn} onChange={(e) => set("colors", p.colors.map((x, j) => (j === i ? { ...x, nameEn: e.target.value } : x)))} />
                <button type="button" className={danger} onClick={() => set("colors", p.colors.filter((_, j) => j !== i))}>✕</button>
              </div>
            ))}
            <button type="button" className={btn2} onClick={() => set("colors", [...p.colors, { nameAr: "", nameEn: "", hex: "#000000" }])}>+ Add colour</button>
          </div>
        </section>
        <section className={card}><h2 className="mb-4 font-medium">SEO</h2>
          <div className="space-y-4">{text("slug", "URL slug", { hint: "Leave empty to generate from the English name" })}{text("seoTitle", "SEO title", { hint: "Defaults to product name" })}{text("seoDesc", "SEO description", { area: true })}</div></section>
      </div>

      <div className="space-y-6 xl:sticky xl:top-6 xl:self-start">
        <section className={card}>
          <div className="space-y-2">{flag("published", "Published (visible in store)")}</div>
          <div className="mt-4 flex items-center gap-3">
            <button onClick={save} disabled={pending} className={btn}>{pending ? "Saving…" : "Save product"}</button>
            {msg && <span className={`text-sm ${msg.ok ? "text-emerald-700" : "text-red-700"}`}>{msg.text}</span>}
          </div>
        </section>
        <section className={`${card} space-y-4`}><h2 className="font-medium">Pricing & stock</h2>
          <div className="grid grid-cols-2 gap-3">
            <Field label="Price"><input type="number" min={0} step="0.01" className={inp} value={p.price} onChange={(e) => set("price", +e.target.value)} /></Field>
            <Field label="Discount price"><input type="number" min={0} step="0.01" className={inp} value={p.salePrice ?? ""} onChange={(e) => set("salePrice", e.target.value === "" ? null : +e.target.value)} /></Field>
            <Field label="SKU"><input className={inp} value={p.sku} onChange={(e) => set("sku", e.target.value)} /></Field>
            <Field label="Stock qty"><input type="number" min={0} className={inp} value={p.stock} onChange={(e) => set("stock", Math.max(0, Math.floor(+e.target.value)))} /></Field>
          </div>
          <Field label="Availability">
            <select className={inp} value={p.availability} onChange={(e) => set("availability", e.target.value as Init["availability"])}>
              <option value="in_stock">In stock (uses stock qty)</option><option value="made_to_order">Made to order</option><option value="out_of_stock">Out of stock</option>
            </select>
          </Field>
        </section>
        <section className={`${card} space-y-4`}><h2 className="font-medium">Organisation</h2>
          <Field label="Category"><select className={inp} value={p.categoryId ?? ""} onChange={(e) => set("categoryId", e.target.value || null)}><option value="">—</option>{categories.map((c) => <option key={c.id} value={c.id}>{c.nameEn}</option>)}</select></Field>
          <Field label="Collection"><select className={inp} value={p.collectionId ?? ""} onChange={(e) => set("collectionId", e.target.value || null)}><option value="">—</option>{collections.map((c) => <option key={c.id} value={c.id}>{c.nameEn}</option>)}</select></Field>
          <div className="space-y-2">{flag("isNew", "New arrival")}{flag("isFeatured", "Featured")}{flag("isLimited", "Limited edition")}{flag("isReady", "Ready to wear / ships now")}</div>
        </section>
        {init.id && <button className={danger} onClick={() => confirm("Delete this product permanently?") && start(async () => { await deleteProduct(init.id!); router.push("/admin/products"); })}>Delete product</button>}
      </div>
    </div>
  );
}
