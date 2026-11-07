import { db } from "@/lib/db";
import { requireStaff } from "@/lib/auth";
import { saveStaff, setRole } from "@/app/admin/actions";
import { Badge, Field, PageHead, btn, btn2, card, inp } from "@/components/admin/ui";

export default async function Staff() {
  const me = await requireStaff(["ADMIN"]);
  const staff = await db.user.findMany({ where: { role: { in: ["ADMIN", "EDITOR"] } }, orderBy: { createdAt: "asc" } });
  return (
    <>
      <PageHead title="Staff & roles" />
      <p className="-mt-4 mb-6 text-sm text-stone"><b>Admin</b>: everything incl. settings & staff. <b>Editor</b>: products, collections, homepage, media, orders, customers, discounts.</p>
      <div className={`${card} mb-6`}>
        <ul className="divide-y divide-black/5 text-sm">
          {staff.map((u) => (
            <li key={u.id} className="flex flex-wrap items-center gap-3 py-2">
              <span className="flex-1">{u.name} <span className="text-stone">· {u.email}</span></span>
              <Badge tone={u.role === "ADMIN" ? "gold" : "neutral"}>{u.role}</Badge>
              {u.id !== me.id && <>
                <form action={setRole.bind(null, u.id, u.role === "ADMIN" ? "EDITOR" : "ADMIN")}><button className={btn2}>Make {u.role === "ADMIN" ? "editor" : "admin"}</button></form>
                <form action={setRole.bind(null, u.id, "CUSTOMER")}><button className="text-red-700">Revoke access</button></form>
              </>}
            </li>
          ))}
        </ul>
      </div>
      <form action={saveStaff} className={`${card} grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end`}>
        <Field label="Name"><input name="name" required className={inp} /></Field>
        <Field label="Email"><input name="email" type="email" required className={inp} /></Field>
        <Field label="Password (min 8)"><input name="password" type="password" minLength={8} required className={inp} autoComplete="new-password" /></Field>
        <Field label="Role"><select name="role" className={inp}><option>EDITOR</option><option>ADMIN</option></select></Field>
        <button className={btn}>Add / update staff</button>
      </form>
    </>
  );
}
