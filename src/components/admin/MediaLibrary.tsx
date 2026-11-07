"use client";
import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { deleteMedia, updateMedia } from "@/app/admin/actions";
import { DropZone, Thumb, type M } from "./media";
import { inp } from "./ui";

export function MediaLibrary({ items }: { items: (M & { used: number })[] }) {
  const router = useRouter();
  const [, start] = useTransition();
  const [kind, setKind] = useState("");
  const kb = (n: number) => (n > 1e6 ? `${(n / 1e6).toFixed(1)} MB` : `${Math.round(n / 1e3)} KB`);
  return (
    <div className="space-y-6">
      <DropZone onUploaded={() => router.refresh()} />
      <div className="flex gap-2 text-sm">{[["", "All"], ["image", "Images"], ["video", "Videos"]].map(([k, l]) => <button key={k} onClick={() => setKind(k)} className={`rounded px-3 py-1 ${kind === k ? "bg-ink text-ivory" : "bg-white"}`}>{l}</button>)}</div>
      <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        {items.filter((m) => !kind || m.kind === kind).map((m) => (
          <li key={m.id} className="rounded border border-black/10 bg-white p-2 text-xs">
            <a href={m.url} target="_blank"><Thumb m={m} className="aspect-[3/4] rounded" /></a>
            <p className="mt-2 text-stone">{m.width ? `${m.width}×${m.height} · ` : ""}{kb(m.size)}{m.used ? ` · in ${m.used} product(s)` : ""}</p>
            <input className={`${inp} mt-1 py-1 text-xs`} dir="rtl" placeholder="Alt (AR)" defaultValue={m.altAr} onBlur={(e) => e.target.value !== m.altAr && start(() => updateMedia(m.id, { altAr: e.target.value }))} />
            <input className={`${inp} mt-1 py-1 text-xs`} placeholder="Alt (EN)" defaultValue={m.altEn} onBlur={(e) => e.target.value !== m.altEn && start(() => updateMedia(m.id, { altEn: e.target.value }))} />
            <div className="mt-2 flex justify-between">
              <button className="underline" onClick={() => navigator.clipboard.writeText(location.origin + m.url)}>Copy URL</button>
              <button className="text-red-700" onClick={() => confirm(m.used ? "This file is used by products. Delete anyway?" : "Delete this file?") && start(async () => { await deleteMedia(m.id); router.refresh(); })}>Delete</button>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}
