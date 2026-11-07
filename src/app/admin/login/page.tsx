"use client";
import Image from "next/image";
import { useActionState } from "react";
import { adminLogin } from "../actions";

export default function Login() {
  const [err, act, pending] = useActionState(adminLogin, null);
  return (
    <div className="grid min-h-screen place-items-center bg-ink p-4">
      <form action={act} className="w-full max-w-sm space-y-6 bg-ivory p-10">
        <Image src="/brand/logo.jpg" alt="Al Kawkaba" width={80} height={80} className="mx-auto rounded-full" />
        <h1 className="display text-center text-3xl">Dashboard</h1>
        <label className="block"><span className="eyebrow text-stone">Email</span><input name="email" type="email" required className="field" autoComplete="email" /></label>
        <label className="block"><span className="eyebrow text-stone">Password</span><input name="password" type="password" required className="field" autoComplete="current-password" /></label>
        {err && <p role="alert" className="text-sm text-red-700">{err}</p>}
        <button disabled={pending} className="btn btn-dark w-full">Sign in</button>
      </form>
    </div>
  );
}
