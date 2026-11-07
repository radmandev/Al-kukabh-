"use client";
import Link from "next/link";
import { usePathname } from "next/navigation";

const LINKS = [
  ["/admin", "Overview"], ["/admin/orders", "Orders"], ["/admin/products", "Products"], ["/admin/collections", "Collections"],
  ["/admin/categories", "Categories"], ["/admin/homepage", "Homepage"], ["/admin/media", "Media library"],
  ["/admin/customers", "Customers"], ["/admin/discounts", "Discount codes"],
];
const ADMIN = [["/admin/settings", "Settings"], ["/admin/staff", "Staff & roles"]];

export function AdminNav({ admin }: { admin: boolean }) {
  const path = usePathname();
  return (
    <nav className="flex gap-1 overflow-x-auto px-3 pb-3 text-sm md:flex-col md:pb-6">
      {[...LINKS, ...(admin ? ADMIN : [])].map(([href, label]) => {
        const on = href === "/admin" ? path === href : path.startsWith(href);
        return <Link key={href} href={href} className={`whitespace-nowrap rounded px-3 py-2 transition ${on ? "bg-white/10 text-gold-soft" : "text-ivory/70 hover:text-ivory"}`}>{label}</Link>;
      })}
    </nav>
  );
}
