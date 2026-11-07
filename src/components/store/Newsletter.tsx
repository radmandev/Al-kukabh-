"use client";
import { useActionState } from "react";
import { subscribe } from "@/app/[locale]/actions";
import type { T } from "@/lib/i18n";

export function Newsletter({ t }: { t: T }) {
  const [state, action, pending] = useActionState(subscribe, null);
  if (state === "ok") return <p className="mt-8 text-gold-soft">{t.subscribed}</p>;
  return (
    <form action={action} className="mx-auto mt-8 flex max-w-md border-b border-ivory/40">
      <label htmlFor="nl" className="sr-only">{t.email}</label>
      <input id="nl" name="email" type="email" required placeholder={t.email} className="flex-1 bg-transparent py-3 text-sm outline-none placeholder:text-ivory/40" />
      <button disabled={pending} className="tracked text-[.68rem] text-gold-soft">{t.subscribe}</button>
    </form>
  );
}
