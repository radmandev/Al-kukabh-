"use client";
import Image from "next/image";
import Link from "next/link";
import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { checkDiscount, placeOrder } from "@/app/[locale]/actions";
import type { Locale, T } from "@/lib/i18n";
import { money } from "@/lib/format";
import { useStore } from "./StoreProvider";

type Props = { l: Locale; t: T; currency: string; methods: { id: string; labelAr: string; labelEn: string }[]; shipping: { flat: number; freeOver: number }; countries: string[]; bankDetails: string; user: { name: string; email: string; phone: string } | null };

export function CheckoutForm({ l, t, currency, methods, shipping, countries, bankDetails, user }: Props) {
  const { lines, clear } = useStore();
  const router = useRouter();
  const [code, setCode] = useState("");
  const [disc, setDisc] = useState<{ amount: number; code: string | null } | null>(null);
  const [codeErr, setCodeErr] = useState(false);
  const [pay, setPay] = useState(methods[0]?.id ?? "");
  const [err, setErr] = useState("");
  const [pending, start] = useTransition();
  const regions = new Intl.DisplayNames([l], { type: "region" });

  const subtotal = lines.reduce((s, x) => s + x.price * x.qty, 0);
  const after = subtotal - (disc?.amount ?? 0);
  const ship = shipping.freeOver > 0 && after >= shipping.freeOver ? 0 : shipping.flat;
  const f = (v: number) => money(v, currency, l);

  if (!lines.length) return (
    <div className="py-40 text-center"><p className="display text-3xl">{t.emptyBag}</p><Link href={`/${l}/shop`} className="btn btn-dark mt-8">{t.continueShopping}</Link></div>
  );

  const submit = (fd: FormData) => start(async () => {
    setErr("");
    const r = await placeOrder({
      ...(Object.fromEntries(["name", "email", "phone", "country", "city", "address", "notes"].map((k) => [k, String(fd.get(k) ?? "")])) as Record<"name" | "email" | "phone" | "country" | "city" | "address" | "notes", string>),
      payment: pay, code: disc?.code ?? undefined,
      lines: lines.map((x) => ({ productId: x.productId, size: x.size, color: x.color, qty: x.qty })),
    }, l);
    if ("error" in r) return setErr((t as Record<string, string>)[r.error] ?? r.error);
    clear();
    if (r.url.startsWith("http")) window.location.href = r.url; else router.push(r.url);
  });

  const input = (name: string, label: string, props: React.InputHTMLAttributes<HTMLInputElement> = {}) => (
    <label className="block"><span className="eyebrow text-stone">{label}</span><input name={name} required className="field" {...props} /></label>
  );

  return (
    <form action={submit} className="mx-auto grid max-w-[1300px] gap-12 px-4 py-12 md:grid-cols-[1.3fr_1fr] md:px-10 md:py-20 lg:gap-24">
      <div className="space-y-12">
        <h1 className="display text-4xl md:text-5xl">{t.checkout}</h1>
        {!user && <p className="text-sm text-stone"><Link href={`/${l}/account?next=/${l}/checkout`} className="link-u text-ink">{t.login}</Link> {t.guest}</p>}
        <fieldset className="space-y-6"><legend className="tracked mb-6 text-xs">{t.contactInfo}</legend>
          {input("name", t.name, { defaultValue: user?.name, autoComplete: "name" })}
          <div className="grid gap-6 sm:grid-cols-2">
            {input("email", t.email, { type: "email", defaultValue: user?.email, autoComplete: "email", dir: "ltr" })}
            {input("phone", t.phone, { type: "tel", defaultValue: user?.phone, autoComplete: "tel", dir: "ltr" })}
          </div>
        </fieldset>
        <fieldset className="space-y-6"><legend className="tracked mb-6 text-xs">{t.shippingAddress}</legend>
          <div className="grid gap-6 sm:grid-cols-2">
            <label className="block"><span className="eyebrow text-stone">{t.country}</span>
              <select name="country" required className="field">{countries.map((c) => <option key={c} value={c}>{regions.of(c) ?? c}</option>)}</select></label>
            {input("city", t.city, { autoComplete: "address-level2" })}
          </div>
          {input("address", t.address, { autoComplete: "street-address" })}
          <label className="block"><span className="eyebrow text-stone">{t.notes}</span><textarea name="notes" rows={2} className="field" /></label>
        </fieldset>
        <fieldset><legend className="tracked mb-6 text-xs">{t.payment}</legend>
          <div className="divide-y divide-line border border-line">
            {methods.map((m) => (
              <label key={m.id} className="flex cursor-pointer items-center gap-4 p-5">
                <input type="radio" name="pay" checked={pay === m.id} onChange={() => setPay(m.id)} className="accent-ink" />
                <span className="text-sm">{l === "ar" ? m.labelAr : m.labelEn}</span>
              </label>
            ))}
          </div>
          {pay === "bank" && bankDetails && <p className="mt-4 whitespace-pre-line bg-sand p-5 text-sm leading-7">{bankDetails}</p>}
        </fieldset>
      </div>

      <aside className="h-fit space-y-6 bg-sand p-6 md:sticky md:top-32 md:p-10">
        <ul className="space-y-5">
          {lines.map((x, i) => (
            <li key={i} className="flex gap-4 text-sm">
              <div className="relative aspect-[3/4] w-16 shrink-0 bg-line">{x.image && <Image src={x.image} alt="" fill sizes="64px" className="object-cover" />}
                <span className="absolute -end-2 -top-2 grid h-5 w-5 place-items-center rounded-full bg-ink text-[.65rem] text-ivory">{x.qty}</span></div>
              <div className="flex-1"><p>{l === "ar" ? x.nameAr : x.nameEn}</p><p className="text-stone">{[x.size, x.color].filter(Boolean).join(" · ")}</p></div>
              <p>{f(x.price * x.qty)}</p>
            </li>
          ))}
        </ul>
        <div className="flex gap-3 border-t border-line pt-6">
          <input value={code} onChange={(e) => setCode(e.target.value)} placeholder={t.discountCode} aria-label={t.discountCode} className="field flex-1 uppercase" />
          <button type="button" className="tracked text-xs" onClick={async () => { const r = await checkDiscount(code, subtotal); setDisc(r); setCodeErr(!r); }}>{t.apply}</button>
        </div>
        {codeErr && <p className="text-sm text-red-700">{t.invalidCode}</p>}
        <dl className="space-y-3 border-t border-line pt-6 text-sm">
          <div className="flex justify-between"><dt>{t.subtotal}</dt><dd>{f(subtotal)}</dd></div>
          {!!disc?.amount && <div className="flex justify-between text-gold"><dt>{t.discount} ({disc.code})</dt><dd>−{f(disc.amount)}</dd></div>}
          <div className="flex justify-between"><dt>{t.shipping}</dt><dd>{ship ? f(ship) : t.free}</dd></div>
          <div className="display flex justify-between border-t border-line pt-4 text-2xl"><dt>{t.total}</dt><dd>{f(after + ship)}</dd></div>
        </dl>
        {err && <p role="alert" className="text-sm text-red-700">{err}</p>}
        <button disabled={pending || !pay} className="btn btn-dark w-full">{pending ? "…" : t.placeOrder}</button>
      </aside>
    </form>
  );
}
