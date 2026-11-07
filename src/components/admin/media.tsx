"use client";
import Image from "next/image";
import { useCallback, useEffect, useRef, useState } from "react";
import { updateMedia } from "@/app/admin/actions";
import { btn, btn2, inp } from "./ui";

export type M = { id: string; url: string; kind: string; mime: string; width: number | null; height: number | null; size: number; altAr: string; altEn: string; posterId: string | null };
export const ACCEPT = "image/jpeg,image/png,image/webp,image/avif,image/gif,video/mp4,video/webm,video/quicktime";

/** XHR upload so we get progress for large videos */
function upload(files: File[], onProgress: (p: number) => void): Promise<{ media: M[]; errors: string[] }> {
  return new Promise((res, rej) => {
    const fd = new FormData();
    files.forEach((f) => fd.append("file", f));
    const x = new XMLHttpRequest();
    x.open("POST", "/api/admin/media");
    x.upload.onprogress = (e) => e.lengthComputable && onProgress(e.loaded / e.total);
    x.onload = () => { try { res(JSON.parse(x.responseText)); } catch { rej(new Error(`Upload failed (${x.status})`)); } };
    x.onerror = () => rej(new Error("Network error"));
    x.send(fd);
  });
}

export function DropZone({ onUploaded, multiple = true, accept = ACCEPT, compact }: { onUploaded: (m: M[]) => void; multiple?: boolean; accept?: string; compact?: boolean }) {
  const [over, setOver] = useState(false);
  const [prog, setProg] = useState<number | null>(null);
  const [errors, setErrors] = useState<string[]>([]);
  const input = useRef<HTMLInputElement>(null);
  const go = async (list: FileList | null) => {
    if (!list?.length) return;
    setErrors([]); setProg(0);
    try {
      const r = await upload(Array.from(list), setProg);
      setErrors(r.errors ?? []);
      if (r.media?.length) onUploaded(r.media);
    } catch (e) { setErrors([(e as Error).message]); }
    setProg(null);
    if (input.current) input.current.value = "";
  };
  return (
    <div>
      <div
        role="button" tabIndex={0} onClick={() => input.current?.click()} onKeyDown={(e) => e.key === "Enter" && input.current?.click()}
        onDragOver={(e) => { e.preventDefault(); setOver(true); }} onDragLeave={() => setOver(false)}
        onDrop={(e) => { e.preventDefault(); setOver(false); go(e.dataTransfer.files); }}
        className={`grid cursor-pointer place-items-center rounded-md border-2 border-dashed text-center text-sm transition ${compact ? "p-4" : "p-10"} ${over ? "border-gold bg-gold/5" : "border-black/15 bg-white hover:border-ink"}`}
      >
        {prog != null ? (
          <div className="w-full max-w-xs"><div className="h-1 overflow-hidden rounded bg-black/10"><div className="h-full bg-gold transition-all" style={{ width: `${prog * 100}%` }} /></div><p className="mt-2 text-xs text-stone">Uploading… {Math.round(prog * 100)}%{prog === 1 && " — optimizing"}</p></div>
        ) : (
          <p><b>Drag & drop</b> {multiple ? "files" : "a file"} here or click to browse<br /><span className="text-xs text-stone">JPG, PNG, WEBP, AVIF, GIF, MP4, WEBM, MOV — images are auto-optimized</span></p>
        )}
      </div>
      <input ref={input} type="file" hidden multiple={multiple} accept={accept} onChange={(e) => go(e.target.files)} />
      {errors.map((e) => <p key={e} className="mt-2 text-xs text-red-700">{e}</p>)}
    </div>
  );
}

export function Thumb({ m, className = "" }: { m: Pick<M, "url" | "kind">; className?: string }) {
  return (
    <div className={`relative overflow-hidden bg-black/5 ${className}`}>
      {m.kind === "video"
        ? <><video src={m.url} muted preload="metadata" className="absolute inset-0 h-full w-full object-cover" /><span className="absolute bottom-1 start-1 rounded bg-black/70 px-1.5 text-[.6rem] text-white">VIDEO</span></>
        : <Image src={m.url} alt="" fill sizes="200px" className="object-cover" />}
    </div>
  );
}

/** Modal: pick from library or upload new */
export function MediaPicker({ open, onClose, onPick, multiple, kind }: { open: boolean; onClose: () => void; onPick: (m: M[]) => void; multiple?: boolean; kind?: "image" | "video" }) {
  const [items, setItems] = useState<M[]>([]);
  const [sel, setSel] = useState<string[]>([]);
  const load = useCallback(() => fetch(`/api/admin/media${kind ? `?kind=${kind}` : ""}`).then((r) => r.json()).then(setItems), [kind]);
  useEffect(() => { if (open) { load(); setSel([]); } }, [open, load]);
  if (!open) return null;
  const pick = (ms: M[]) => { onPick(ms); onClose(); };
  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal>
      <div className="flex max-h-[90vh] w-full max-w-5xl flex-col rounded-md bg-[#f4f2ee]">
        <div className="flex items-center justify-between border-b border-black/10 p-4"><h2 className="font-medium">Media library</h2><button onClick={onClose} className={btn2}>Close</button></div>
        <div className="overflow-y-auto p-4">
          <DropZone compact multiple={multiple} accept={kind === "image" ? "image/*" : kind === "video" ? "video/*" : ACCEPT}
            onUploaded={(ms) => (multiple ? (setItems((x) => [...ms, ...x]), setSel((s) => [...s, ...ms.map((m) => m.id)])) : pick(ms.slice(0, 1)))} />
          <div className="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-7">
            {items.map((m) => {
              const on = sel.includes(m.id);
              return (
                <button key={m.id} type="button" onClick={() => (multiple ? setSel((s) => (on ? s.filter((x) => x !== m.id) : [...s, m.id])) : pick([m]))}
                  className={`relative rounded ring-2 ${on ? "ring-gold" : "ring-transparent hover:ring-black/20"}`}>
                  <Thumb m={m} className="aspect-[3/4] rounded" />
                  {on && <span className="absolute end-1 top-1 grid h-5 w-5 place-items-center rounded-full bg-gold text-[.65rem] text-white">{sel.indexOf(m.id) + 1}</span>}
                </button>
              );
            })}
          </div>
        </div>
        {multiple && <div className="flex justify-end gap-2 border-t border-black/10 p-4"><button className={btn} disabled={!sel.length} onClick={() => pick(sel.map((id) => items.find((m) => m.id === id)!))}>Add {sel.length || ""} selected</button></div>}
      </div>
    </div>
  );
}

export type Ref = { id?: string; url: string; kind: string; poster?: string; altAr?: string; altEn?: string };

/** Single media slot used in homepage sections (image or video + optional poster + alt) */
export function MediaField({ value, onChange }: { value?: Ref; onChange: (v?: Ref) => void }) {
  const [open, setOpen] = useState<"main" | "poster" | null>(null);
  return (
    <div className="flex items-start gap-3">
      {value?.url ? <Thumb m={value} className="aspect-[4/5] w-24 shrink-0 rounded" /> : <div className="grid aspect-[4/5] w-24 shrink-0 place-items-center rounded border border-dashed border-black/20 text-xs text-stone">Empty</div>}
      <div className="flex-1 space-y-2">
        <div className="flex flex-wrap gap-2">
          <button type="button" className={btn2} onClick={() => setOpen("main")}>{value?.url ? "Replace" : "Choose / upload"}</button>
          {value?.kind === "video" && <button type="button" className={btn2} onClick={() => setOpen("poster")}>{value.poster ? "Change" : "Add"} video thumbnail</button>}
          {value?.url && <button type="button" className="text-sm text-red-700" onClick={() => onChange(undefined)}>Remove</button>}
        </div>
        {value?.url && (
          <div className="grid gap-2 sm:grid-cols-2">
            <input className={inp} placeholder="Alt text (AR)" dir="rtl" value={value.altAr ?? ""} onChange={(e) => onChange({ ...value, altAr: e.target.value })} />
            <input className={inp} placeholder="Alt text (EN)" value={value.altEn ?? ""} onChange={(e) => onChange({ ...value, altEn: e.target.value })} />
          </div>
        )}
      </div>
      <MediaPicker open={open === "main"} onClose={() => setOpen(null)} onPick={([m]) => onChange({ id: m.id, url: m.url, kind: m.kind, altAr: m.altAr || value?.altAr, altEn: m.altEn || value?.altEn })} />
      <MediaPicker open={open === "poster"} kind="image" onClose={() => setOpen(null)} onPick={([m]) => value && onChange({ ...value, poster: m.url })} />
    </div>
  );
}

/** Ordered product gallery: drag to reorder, first = primary, per-item alt text & video thumbnail */
export function MediaManager({ items, onChange }: { items: M[]; onChange: (m: M[]) => void }) {
  const [picker, setPicker] = useState(false);
  const [posterFor, setPosterFor] = useState<string | null>(null);
  const [drag, setDrag] = useState<number | null>(null);
  const move = (from: number, to: number) => { const a = [...items]; const [x] = a.splice(from, 1); a.splice(to, 0, x); onChange(a); };
  const patch = (id: string, d: Partial<M>) => { onChange(items.map((m) => (m.id === id ? { ...m, ...d } : m))); updateMedia(id, d); };
  const add = (ms: M[]) => onChange([...items, ...ms.filter((m) => !items.some((x) => x.id === m.id))]);
  return (
    <div className="space-y-4">
      <DropZone onUploaded={add} />
      <div className="flex justify-between text-xs text-stone"><span>Drag to reorder · first item is the primary image</span><button type="button" className="underline" onClick={() => setPicker(true)}>Choose from library</button></div>
      <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        {items.map((m, i) => (
          <li key={m.id} draggable onDragStart={() => setDrag(i)} onDragOver={(e) => e.preventDefault()} onDrop={() => { if (drag != null && drag !== i) move(drag, i); setDrag(null); }}
            className={`rounded border bg-white p-2 ${i === 0 ? "border-gold" : "border-black/10"} ${drag === i ? "opacity-40" : ""}`}>
            <div className="relative cursor-grab"><Thumb m={m} className="aspect-[3/4] rounded" />
              {i === 0 && <span className="absolute start-1 top-1 rounded bg-gold px-1.5 text-[.6rem] text-white">PRIMARY</span>}</div>
            <input className={`${inp} mt-2 py-1 text-xs`} dir="rtl" placeholder="Alt (AR)" defaultValue={m.altAr} onBlur={(e) => e.target.value !== m.altAr && patch(m.id, { altAr: e.target.value })} />
            <input className={`${inp} mt-1 py-1 text-xs`} placeholder="Alt (EN)" defaultValue={m.altEn} onBlur={(e) => e.target.value !== m.altEn && patch(m.id, { altEn: e.target.value })} />
            <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs">
              {i > 0 && <button type="button" className="underline" onClick={() => move(i, 0)}>Make primary</button>}
              {i > 0 && <button type="button" aria-label="Move earlier" onClick={() => move(i, i - 1)}>←</button>}
              {i < items.length - 1 && <button type="button" aria-label="Move later" onClick={() => move(i, i + 1)}>→</button>}
              {m.kind === "video" && <button type="button" className="underline" onClick={() => setPosterFor(m.id)}>{m.posterId ? "Change" : "Add"} thumbnail</button>}
              <button type="button" className="ms-auto text-red-700" onClick={() => onChange(items.filter((x) => x.id !== m.id))}>Remove</button>
            </div>
          </li>
        ))}
      </ul>
      <MediaPicker open={picker} multiple onClose={() => setPicker(false)} onPick={add} />
      <MediaPicker open={!!posterFor} kind="image" onClose={() => setPosterFor(null)} onPick={([p]) => posterFor && patch(posterFor, { posterId: p.id })} />
    </div>
  );
}
