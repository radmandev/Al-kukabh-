import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { dict, type Locale } from "@/lib/i18n";

export default async function Track({ params, searchParams }: { params: Promise<{ locale: Locale }>; searchParams: Promise<{ n?: string; e?: string }> }) {
  const [{ locale: l }, q] = await Promise.all([params, searchParams]);
  const t = dict(l);
  let notFound = false;
  if (q.n && q.e) {
    const o = await db.order.findUnique({ where: { number: q.n.trim().toUpperCase() } });
    if (o && o.email === q.e.trim().toLowerCase()) redirect(`/${l}/order/${o.number}?e=${encodeURIComponent(o.email)}`);
    notFound = true;
  }
  return (
    <div className="mx-auto max-w-md px-4 py-20 md:py-32">
      <h1 className="display text-center text-4xl">{t.trackOrder}</h1>
      <form className="mt-12 space-y-6">
        <label className="block"><span className="eyebrow text-stone">{t.orderNumber}</span><input name="n" required defaultValue={q.n} className="field uppercase" dir="ltr" /></label>
        <label className="block"><span className="eyebrow text-stone">{t.email}</span><input name="e" type="email" required defaultValue={q.e} className="field" dir="ltr" /></label>
        {notFound && <p role="alert" className="text-sm text-red-700">{t.orderNotFound}</p>}
        <button className="btn btn-dark w-full">{t.track}</button>
      </form>
    </div>
  );
}
