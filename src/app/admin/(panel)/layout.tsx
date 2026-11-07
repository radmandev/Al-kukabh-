import Link from "next/link";
import Image from "next/image";
import { requireStaff } from "@/lib/auth";
import { adminLogout } from "../actions";
import { AdminNav } from "@/components/admin/AdminNav";

export default async function Panel({ children }: { children: React.ReactNode }) {
  const u = await requireStaff();
  return (
    <div className="md:grid md:grid-cols-[230px_1fr]">
      <aside className="bg-ink text-ivory md:sticky md:top-0 md:h-screen md:overflow-y-auto">
        <div className="flex items-center gap-3 p-5">
          <Image src="/brand/logo.jpg" alt="" width={40} height={40} className="rounded-full" />
          <div className="leading-tight"><p className="display text-lg">Al Kawkaba</p><p className="text-[.65rem] uppercase tracking-widest text-gold-soft">{u.role.toLowerCase()}</p></div>
        </div>
        <AdminNav admin={u.role === "ADMIN"} />
        <div className="flex gap-4 border-t border-white/10 p-5 text-xs text-ivory/60">
          <Link href="/" target="_blank" className="hover:text-ivory">View store ↗</Link>
          <form action={adminLogout}><button className="hover:text-ivory">Sign out</button></form>
        </div>
      </aside>
      <main className="min-w-0 p-4 md:p-10">{children}</main>
    </div>
  );
}
