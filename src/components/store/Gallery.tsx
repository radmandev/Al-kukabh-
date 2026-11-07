"use client";
import Image from "next/image";
import { useEffect, useRef, useState } from "react";
import { Icon } from "@/components/Icon";

export type GItem = { url: string; kind: string; poster?: string; alt: string };

function Slide({ g, priority, sizes }: { g: GItem; priority?: boolean; sizes: string }) {
  return g.kind === "video"
    ? <video src={g.url} poster={g.poster} className="absolute inset-0 h-full w-full object-cover" muted loop playsInline autoPlay aria-label={g.alt} />
    : <Image src={g.url} alt={g.alt} fill priority={priority} sizes={sizes} className="object-cover" />;
}

export function Gallery({ items, name }: { items: GItem[]; name: string }) {
  const [i, setI] = useState(0);
  const [full, setFull] = useState(false);
  const [zoom, setZoom] = useState<{ x: number; y: number } | null>(null);
  const strip = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!full) return;
    document.body.style.overflow = "hidden";
    const k = (e: KeyboardEvent) => {
      if (e.key === "Escape") setFull(false);
      const rtl = document.dir === "rtl";
      if (e.key === "ArrowRight") setI((x) => (x + (rtl ? -1 : 1) + items.length) % items.length);
      if (e.key === "ArrowLeft") setI((x) => (x + (rtl ? 1 : -1) + items.length) % items.length);
    };
    window.addEventListener("keydown", k);
    return () => { document.body.style.overflow = ""; window.removeEventListener("keydown", k); };
  }, [full, items.length]);
  useEffect(() => setZoom(null), [i, full]);

  if (!items.length) return <div className="aspect-[3/4] bg-sand" />;
  const cur = items[i];
  const onScroll = () => { const el = strip.current!; setI(Math.round(Math.abs(el.scrollLeft) / el.clientWidth)); };

  return (
    <>
      {/* mobile: swipe */}
      <div className="relative md:hidden">
        <div ref={strip} onScroll={onScroll} className="no-scrollbar flex snap-x snap-mandatory overflow-x-auto">
          {items.map((g, j) => (
            <button key={g.url} onClick={() => { setI(j); setFull(true); }} className="relative aspect-[3/4] w-full shrink-0 snap-center bg-sand" aria-label={`${name} ${j + 1}`}>
              <Slide g={g} priority={j === 0} sizes="100vw" />
            </button>
          ))}
        </div>
        <div className="absolute inset-x-0 bottom-4 flex justify-center gap-1.5">
          {items.map((_, j) => <span key={j} className={`h-1 rounded-full transition-all ${j === i ? "w-5 bg-ink" : "w-1 bg-ink/30"}`} />)}
        </div>
      </div>

      {/* desktop: vertical thumbs + main */}
      <div className="hidden gap-4 md:grid md:grid-cols-[84px_1fr]">
        <div className="no-scrollbar flex max-h-[calc((100vw-5rem)*0.55*1.33)] flex-col gap-3 overflow-y-auto lg:max-h-[85vh]">
          {items.map((g, j) => (
            <button key={g.url} onMouseEnter={() => setI(j)} onClick={() => setI(j)} aria-label={`${name} ${j + 1}`} aria-current={j === i}
              className={`relative aspect-[3/4] w-full shrink-0 bg-sand transition ${j === i ? "opacity-100 ring-1 ring-ink" : "opacity-60 hover:opacity-100"}`}>
              {g.kind === "video" ? (g.poster ? <Image src={g.poster} alt="" fill sizes="84px" className="object-cover" /> : <video src={g.url} className="absolute inset-0 h-full w-full object-cover" muted preload="metadata" />) : <Image src={g.url} alt="" fill sizes="84px" className="object-cover" />}
              {g.kind === "video" && <Icon name="play" filled className="absolute inset-0 m-auto h-5 w-5 text-ivory" />}
            </button>
          ))}
        </div>
        <button onClick={() => setFull(true)} className="group relative aspect-[3/4] cursor-zoom-in overflow-hidden bg-sand" aria-label="Open full screen">
          <div key={cur.url} className="reveal absolute inset-0"><Slide g={cur} priority sizes="55vw" /></div>
          <Icon name="expand" className="absolute bottom-4 end-4 h-5 w-5 opacity-0 transition group-hover:opacity-100" />
        </button>
      </div>

      {full && (
        <div className="fixed inset-0 z-[100] bg-ivory" role="dialog" aria-modal aria-label={name}>
          <div
            className={`absolute inset-0 overflow-hidden ${cur.kind === "image" ? (zoom ? "cursor-zoom-out" : "cursor-zoom-in") : ""}`}
            onClick={(e) => { if (cur.kind !== "image") return; const r = e.currentTarget.getBoundingClientRect(); setZoom(zoom ? null : { x: ((e.clientX - r.left) / r.width) * 100, y: ((e.clientY - r.top) / r.height) * 100 }); }}
            onMouseMove={(e) => { if (!zoom) return; const r = e.currentTarget.getBoundingClientRect(); setZoom({ x: ((e.clientX - r.left) / r.width) * 100, y: ((e.clientY - r.top) / r.height) * 100 }); }}
          >
            {cur.kind === "video"
              ? <video src={cur.url} poster={cur.poster} className="h-full w-full object-contain" controls autoPlay playsInline />
              : <Image src={cur.url} alt={cur.alt} fill sizes="100vw" quality={90} className="object-contain transition-transform duration-300" style={zoom ? { transform: "scale(2.2)", transformOrigin: `${zoom.x}% ${zoom.y}%` } : undefined} />}
          </div>
          <button onClick={() => setFull(false)} className="absolute end-5 top-5 bg-ivory/80 p-2" aria-label="Close"><Icon name="close" className="h-6 w-6" /></button>
          {items.length > 1 && (
            <>
              <button onClick={() => setI((i - 1 + items.length) % items.length)} className="absolute start-4 top-1/2 -translate-y-1/2 bg-ivory/80 p-3" aria-label="Previous"><Icon name="arrow" className="h-5 w-5 rotate-180 rtl:rotate-0" /></button>
              <button onClick={() => setI((i + 1) % items.length)} className="absolute end-4 top-1/2 -translate-y-1/2 bg-ivory/80 p-3" aria-label="Next"><Icon name="arrow" className="h-5 w-5 rtl:rotate-180" /></button>
              <p className="absolute inset-x-0 bottom-5 text-center text-xs tracking-widest">{i + 1} / {items.length}</p>
            </>
          )}
        </div>
      )}
    </>
  );
}
