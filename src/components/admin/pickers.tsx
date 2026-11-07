"use client";
import { useState, useTransition } from "react";
import { reorder } from "@/app/admin/actions";
import { inp } from "./ui";

type Item = { id: string; label: string; sub?: string };

/** Searchable multi-select with order preserved (selected items listed first, reorderable). */
export function ItemPicker({ items, value, onChange }: { items: Item[]; value: string[]; onChange: (ids: string[]) => void }) {
  const [q, setQ] = useState("");
  const chosen = value.map((id) => items.find((i) => i.id === id)).filter(Boolean) as Item[];
  const rest = items.filter((i) => !value.includes(i.id) && (i.label + (i.sub ?? "")).toLowerCase().includes(q.toLowerCase()));
  const move = (i: number, d: number) => { const a = [...value]; [a[i], a[i + d]] = [a[i + d], a[i]]; onChange(a); };
  return (
    <div className="rounded border border-black/10">
      {chosen.length > 0 && (
        <ul className="divide-y divide-black/5 border-b border-black/10 bg-gold/5">
          {chosen.map((it, i) => (
            <li key={it.id} className="flex items-center gap-2 px-3 py-1.5 text-sm">
              <span className="w-5 text-xs text-stone">{i + 1}</span><span className="flex-1">{it.label}</span>
              {i > 0 && <button type="button" onClick={() => move(i, -1)} aria-label="Up">↑</button>}
              {i < chosen.length - 1 && <button type="button" onClick={() => move(i, 1)} aria-label="Down">↓</button>}
              <button type="button" className="text-red-700" onClick={() => onChange(value.filter((x) => x !== it.id))}>✕</button>
            </li>
          ))}
        </ul>
      )}
      <div className="p-2"><input className={inp} placeholder="Search to add…" value={q} onChange={(e) => setQ(e.target.value)} /></div>
      <ul className="max-h-56 overflow-y-auto text-sm">
        {rest.map((it) => <li key={it.id}><button type="button" className="w-full px-3 py-1.5 text-start hover:bg-black/5" onClick={() => onChange([...value, it.id])}>+ {it.label} <span className="text-xs text-stone">{it.sub}</span></button></li>)}
      </ul>
    </div>
  );
}

/** Rows with ↑/↓ that persist order via reorder() */
export function SortableRows({ model, rows }: { model: "collection" | "category" | "section"; rows: { id: string; node: React.ReactNode }[] }) {
  const [order, setOrder] = useState(rows.map((r) => r.id));
  const [, start] = useTransition();
  const byId = new Map(rows.map((r) => [r.id, r.node]));
  const ids = order.filter((id) => byId.has(id)).concat(rows.map((r) => r.id).filter((id) => !order.includes(id)));
  const move = (i: number, d: number) => { const a = [...ids]; [a[i], a[i + d]] = [a[i + d], a[i]]; setOrder(a); start(() => reorder(model, a)); };
  return (
    <ul className="divide-y divide-black/5 rounded-md border border-black/10 bg-white">
      {ids.map((id, i) => (
        <li key={id} className="flex items-center gap-3 p-3">
          <div className="flex flex-col text-xs leading-none text-stone">
            <button disabled={i === 0} onClick={() => move(i, -1)} className="p-1 disabled:opacity-20" aria-label="Move up">▲</button>
            <button disabled={i === ids.length - 1} onClick={() => move(i, 1)} className="p-1 disabled:opacity-20" aria-label="Move down">▼</button>
          </div>
          <div className="min-w-0 flex-1">{byId.get(id)}</div>
        </li>
      ))}
    </ul>
  );
}
