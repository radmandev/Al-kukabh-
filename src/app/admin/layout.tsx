import "../globals.css";
import { fontVars } from "@/lib/fonts";

export const metadata = { title: "Al Kawkaba — Admin", robots: { index: false } };

export default function AdminRoot({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" dir="ltr" className={fontVars}>
      <body className="min-h-screen bg-[#f4f2ee] text-ink">{children}</body>
    </html>
  );
}
