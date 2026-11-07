"use client";
import { useActionState } from "react";
import { login, register } from "@/app/[locale]/actions";
import type { Locale, T } from "@/lib/i18n";

export function AuthForms({ l, t, next }: { l: Locale; t: T; next?: string }) {
  const [le, loginAct, lp] = useActionState(login, null);
  const [re, regAct, rp] = useActionState(register, null);
  const msg = (k: string | null) => k && <p role="alert" className="text-sm text-red-700">{(t as Record<string, string>)[k] ?? k}</p>;
  const f = (name: string, label: string, p: React.InputHTMLAttributes<HTMLInputElement> = {}) => (
    <label className="block"><span className="eyebrow text-stone">{label}</span><input name={name} required className="field" {...p} /></label>
  );
  return (
    <div className="mx-auto grid max-w-5xl gap-16 px-4 py-16 md:grid-cols-2 md:py-24">
      <form action={loginAct} className="space-y-6">
        <h1 className="display text-4xl">{t.login}</h1>
        <input type="hidden" name="locale" value={l} /><input type="hidden" name="next" value={next ?? ""} />
        {f("email", t.email, { type: "email", autoComplete: "email", dir: "ltr" })}
        {f("password", t.password, { type: "password", autoComplete: "current-password" })}
        {msg(le)}
        <button disabled={lp} className="btn btn-dark w-full">{t.login}</button>
      </form>
      <form action={regAct} className="space-y-6">
        <h2 className="display text-4xl">{t.register}</h2>
        <input type="hidden" name="locale" value={l} />
        {f("name", t.name, { autoComplete: "name" })}
        {f("email", t.email, { type: "email", autoComplete: "email", dir: "ltr" })}
        {f("phone", t.phone, { type: "tel", required: false, autoComplete: "tel", dir: "ltr" })}
        {f("password", t.password, { type: "password", minLength: 8, autoComplete: "new-password" })}
        {msg(re)}
        <button disabled={rp} className="btn w-full">{t.register}</button>
      </form>
    </div>
  );
}
