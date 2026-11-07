"use client";
import { useRef } from "react";
import { Icon } from "@/components/Icon";

/** Horizontal scroll-snap rail with arrow controls (desktop) and swipe (mobile). */
export function Rail({ children, label }: { children: React.ReactNode; label: string }) {
  const ref = useRef<HTMLDivElement>(null);
  const by = (d: number) => {
    const el = ref.current!;
    const rtl = getComputedStyle(el).direction === "rtl";
    el.scrollBy({ left: d * (rtl ? -1 : 1) * el.clientWidth * 0.8, behavior: "smooth" });
  };
  return (
    <div className="relative">
      <div ref={ref} role="region" aria-label={label} className="no-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-4 px-4 md:gap-6 md:scroll-px-10 md:px-10">
        {children}
      </div>
      <div className="mt-8 hidden justify-center gap-2 md:flex">
        <button onClick={() => by(-1)} className="border border-line p-3 hover:border-ink" aria-label="Previous"><Icon name="arrow" className="h-4 w-4 rotate-180 rtl:rotate-0" /></button>
        <button onClick={() => by(1)} className="border border-line p-3 hover:border-ink" aria-label="Next"><Icon name="arrow" className="h-4 w-4 rtl:rotate-180" /></button>
      </div>
    </div>
  );
}
