import { db } from "@/lib/db";
import { saveDiscount, toggleDiscount, deleteDiscount } from "@/app/admin/actions";
import { Badge, Field, PageHead, btn, btn2, card, inp } from "@/components/admin/ui";

export default async function Discounts() {
  const ds = await db.discount.findMany({ orderBy: { code: "asc" } });
  return (
    <>
      <PageHead title="Discount codes" />
      <form action={saveDiscount} className={`${card} mb-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-7 lg:items-end`}>
        <Field label="Code"><input name="code" required className={`${inp} uppercase`} /></Field>
        <Field label="Type"><select name="type" className={inp}><option value="percent">% off</option><option value="fixed">Fixed amount</option></select></Field>
        <Field label="Value"><input name="value" type="number" step="0.01" min="0.01" required className={inp} /></Field>
        <Field label="Min. order"><input name="minTotal" type="number" min="0" defaultValue={0} className={inp} /></Field>
        <Field label="Max uses"><input name="maxUses" type="number" min="1" placeholder="∞" className={inp} /></Field>
        <Field label="Expires"><input name="expiresAt" type="date" className={inp} /></Field>
        <button className={btn}>Save code</button>
      </form>
      <div className={`${card} overflow-x-auto`}>
        <table className="w-full min-w-[600px] text-sm">
          <thead className="text-xs text-stone"><tr>{["Code", "Discount", "Min. order", "Used", "Expires", "", ""].map((h, i) => <th key={i} className="pb-2 text-start font-medium">{h}</th>)}</tr></thead>
          <tbody className="divide-y divide-black/5">
            {ds.map((d) => (
              <tr key={d.id}>
                <td className="py-2 font-mono">{d.code}</td><td>{d.type === "percent" ? `${d.value}%` : d.value}</td><td>{d.minTotal || "—"}</td>
                <td>{d.used}{d.maxUses ? ` / ${d.maxUses}` : ""}</td><td>{d.expiresAt?.toLocaleDateString("en-GB") ?? "—"}</td>
                <td><form action={toggleDiscount.bind(null, d.id, !d.active)}><button className={btn2}>{d.active ? <Badge tone="good">Active</Badge> : <Badge>Off</Badge>}</button></form></td>
                <td><form action={deleteDiscount.bind(null, d.id)}><button className="text-red-700">Delete</button></form></td>
              </tr>
            ))}
          </tbody>
        </table>
        {!ds.length && <p className="py-6 text-center text-stone">No codes yet.</p>}
      </div>
    </>
  );
}
