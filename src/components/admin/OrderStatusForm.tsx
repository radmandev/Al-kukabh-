"use client";
import { useState, useTransition } from "react";
import { updateOrder } from "@/app/admin/actions";
import { Field, btn, inp } from "./ui";

const OPTS = {
  status: ["pending", "confirmed", "processing", "completed", "cancelled"],
  paymentStatus: ["pending", "paid", "failed", "refunded"],
  shipStatus: ["unfulfilled", "shipped", "delivered", "returned"],
};

export function OrderStatusForm({ id, init }: { id: string; init: { status: string; paymentStatus: string; shipStatus: string; tracking: string } }) {
  const [d, setD] = useState({ ...init, note: "" });
  const [msg, setMsg] = useState("");
  const [pending, start] = useTransition();
  return (
    <div className="space-y-4">
      {(Object.keys(OPTS) as (keyof typeof OPTS)[]).map((k) => (
        <Field key={k} label={{ status: "Order status", paymentStatus: "Payment status", shipStatus: "Shipping status" }[k]}>
          <select className={inp} value={d[k]} onChange={(e) => setD({ ...d, [k]: e.target.value })}>{OPTS[k].map((o) => <option key={o}>{o}</option>)}</select>
        </Field>
      ))}
      <Field label="Tracking number / link"><input className={inp} value={d.tracking} onChange={(e) => setD({ ...d, tracking: e.target.value })} /></Field>
      <Field label="Internal note (added to history)"><textarea rows={2} className={inp} value={d.note} onChange={(e) => setD({ ...d, note: e.target.value })} /></Field>
      {d.status === "cancelled" && init.status !== "cancelled" && <p className="text-xs text-amber-800">Cancelling returns in-stock items to inventory.</p>}
      <div className="flex items-center gap-3">
        <button className={btn} disabled={pending} onClick={() => start(async () => { await updateOrder(id, d); setD({ ...d, note: "" }); setMsg("Updated ✓"); })}>{pending ? "Saving…" : "Update order"}</button>
        <span className="text-sm text-emerald-700">{msg}</span>
      </div>
    </div>
  );
}
