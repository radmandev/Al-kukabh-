"use client";
import Link from "next/link";
import { useEffect, useState } from "react";
import { ResponsiveMedia } from "@/components/Media";

type Slide = { desktop?: { url: string; kind: string; poster?: string; alt?: string }; mobile?: { url: string; kind: string; poster?: string; alt?: string }; eyebrow: string; heading: string; text: string; cta: string; link: string };

export function Hero({ slides }: { slides: Slide[] }) {
  const [i, setI] = useState(0);
  const [paused, setPaused] = useState(false);
  useEffect(() => {
    if (slides.length < 2 || paused) return;
    const id = setTimeout(() => setI((i + 1) % slides.length), 7000);
    return () => clearTimeout(id);
  }, [i, slides.length, paused]);
  if (!slides.length) return null;
  return (
    <section className="relative h-[100svh] min-h-[560px] overflow-hidden bg-ink text-ivory" aria-roledescription="carousel" onMouseEnter={() => setPaused(true)} onMouseLeave={() => setPaused(false)}>
      {slides.map((s, j) => (
        <div key={j} className={`absolute inset-0 transition-opacity duration-[1.6s] ease-[var(--ease-lux)] ${j === i ? "opacity-100" : "pointer-events-none opacity-0"}`} aria-hidden={j !== i}>
          <div className={`absolute inset-0 ${j === i ? "kenburns" : ""}`}>
            <ResponsiveMedia desktop={s.desktop} mobile={s.mobile} priority={j === 0} />
          </div>
          <div className="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-black/20" />
          {j === i && (
            <div className="absolute inset-x-0 bottom-0 mx-auto max-w-4xl px-6 pb-20 text-center md:pb-28">
              {s.eyebrow && <p className="eyebrow reveal text-gold-soft">{s.eyebrow}</p>}
              {s.heading && <h1 className="display reveal mt-4 text-5xl [animation-delay:.15s] md:text-7xl">{s.heading}</h1>}
              {s.text && <p className="reveal mx-auto mt-5 max-w-lg text-sm leading-7 text-ivory/80 [animation-delay:.3s] md:text-base">{s.text}</p>}
              {s.cta && s.link && <Link href={s.link} className="btn btn-ghost reveal mt-9 [animation-delay:.45s]"><span>{s.cta}</span></Link>}
            </div>
          )}
        </div>
      ))}
      {slides.length > 1 && (
        <div className="absolute inset-x-0 bottom-8 flex justify-center gap-3">
          {slides.map((_, j) => (
            <button key={j} onClick={() => setI(j)} aria-label={`${j + 1}`} aria-current={j === i} className="h-6 w-10 py-3">
              <span className={`block h-px w-full transition ${j === i ? "bg-ivory" : "bg-ivory/35"}`} />
            </button>
          ))}
        </div>
      )}
    </section>
  );
}
