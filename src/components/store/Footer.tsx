import Link from "next/link";
import Image from "next/image";
import { L, type Locale, type T } from "@/lib/i18n";
import type { Settings } from "@/lib/settings";
import { Newsletter } from "./Newsletter";

export function Footer({ l, t, s }: { l: Locale; t: T; s: Settings }) {
  const social = Object.entries(s.social).filter(([, v]) => v);
  const col = (title: string, links: [string, string][]) => (
    <div>
      <p className="eyebrow mb-5 text-gold-soft">{title}</p>
      <ul className="space-y-3 text-sm text-ivory/70">
        {links.map(([href, label]) => <li key={href}><Link href={href} className="link-u hover:text-ivory">{label}</Link></li>)}
      </ul>
    </div>
  );
  return (
    <footer className="bg-ink text-ivory">
      <div className="border-b border-white/10">
        <div className="mx-auto max-w-3xl px-6 py-20 text-center">
          <p className="display text-3xl md:text-4xl">{t.newsletter}</p>
          <p className="mx-auto mt-4 max-w-md text-sm text-ivory/60">{t.newsletterText}</p>
          <Newsletter t={t} />
        </div>
      </div>
      <div className="mx-auto grid max-w-[1400px] gap-12 px-6 py-16 md:grid-cols-[1.4fr_1fr_1fr_1fr] md:px-10">
        <div>
          <Image src={s.brand.logoUrl} alt={L(s.brand, "name", l)} width={96} height={96} className="h-24 w-24 rounded-full object-cover" />
          <p className="mt-6 max-w-xs text-sm leading-7 text-ivory/60">{L(s.brand, "about", l)}</p>
        </div>
        {col(t.shop, [[`/${l}/shop?tag=new`, t.newArrivals], [`/${l}/collections`, t.collections], [`/${l}/shop?category=occasion`, t.occasion], [`/${l}/shop?category=everyday`, t.everyday], [`/${l}/shop?tag=limited`, t.limited]])}
        {col(t.customerService, [[`/${l}/track`, t.trackOrder], [`/${l}/policies/shipping`, t.shippingPolicy], [`/${l}/policies/returns`, t.returns], [`/${l}/policies/privacy`, t.privacy], [`/${l}/policies/terms`, t.terms]])}
        <div>
          <p className="eyebrow mb-5 text-gold-soft">{t.contact}</p>
          <ul className="space-y-3 text-sm text-ivory/70">
            {s.contact.email && <li><a href={`mailto:${s.contact.email}`} className="link-u">{s.contact.email}</a></li>}
            {s.contact.phone && <li dir="ltr" className="text-start"><a href={`tel:${s.contact.phone}`} className="link-u">{s.contact.phone}</a></li>}
            {s.contact.whatsapp && <li><a href={`https://wa.me/${s.contact.whatsapp.replace(/\D/g, "")}`} className="link-u">WhatsApp</a></li>}
            {L(s.contact, "address", l) && <li>{L(s.contact, "address", l)}</li>}
          </ul>
          {social.length > 0 && (
            <>
              <p className="eyebrow mb-4 mt-8 text-gold-soft">{t.followUs}</p>
              <div className="flex flex-wrap gap-4 text-sm capitalize text-ivory/70">
                {social.map(([k, v]) => <a key={k} href={v} target="_blank" rel="noopener" className="link-u">{k}</a>)}
              </div>
            </>
          )}
        </div>
      </div>
      <p className="border-t border-white/10 py-6 text-center text-xs text-ivory/40">© {new Date().getFullYear()} {L(s.brand, "name", l)} — {t.rights}</p>
    </footer>
  );
}
