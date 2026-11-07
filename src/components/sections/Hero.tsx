"use client";
import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { ResponsiveMedia } from "@/components/Media";
import { Icon } from "@/components/Icon";

type MM = { url: string; kind: string; poster?: string; alt?: string };
type Slide = { desktop?: MM; mobile?: MM; eyebrow: string; heading: string; text: string; cta: string; link: string };

const IMAGE_MS = 7000;
const VIDEO_MAX_MS = 90000; // safety net if a video never fires "ended" (e.g. autoplay blocked)

export function Hero({ slides }: { slides: Slide[] }) {
  const [i, setI] = useState(0);
  const [paused, setPaused] = useState(false);
  const [mobile, setMobile] = useState(false);
  const vids = useRef<(HTMLVideoElement | null)[]>([]);
  const next = () => setI((x) => (x + 1) % slides.length);

  useEffect(() => {
    const q = matchMedia("(max-width: 767px)");
    const f = () => setMobile(q.matches);
    f(); q.addEventListener("change", f);
    return () => q.removeEventListener("change", f);
  }, []);

  const pick = (s: Slide) => (mobile && s.mobile?.url ? s.mobile : s.desktop);
  const cur = slides[i] && pick(slides[i]);
  const isVideo = cur?.kind === "video";

  // images advance on a timer; videos advance when they end
  useEffect(() => {
    if (slides.length < 2 || paused) return;
    const id = setTimeout(next, isVideo ? VIDEO_MAX_MS : IMAGE_MS);
    return () => clearTimeout(id);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [i, slides.length, paused, isVideo]);

  // restart the active video from the beginning, pause the others
  useEffect(() => {
    vids.current.forEach((v, j) => {
      if (!v) return;
      if (j === i && !paused) { v.currentTime = 0; v.play().catch(() => {}); } else v.pause();
    });
  }, [i, paused, mobile]);

  if (!slides.length) return null;
  return (
    <section className="relative h-[100svh] min-h-[560px] overflow-hidden bg-ink text-ivory" aria-roledescription="carousel">
      {slides.map((s, j) => {
        const m = pick(s);
        return (
          <div key={j} className={`absolute inset-0 transition-opacity duration-[1.6s] ease-[var(--ease-lux)] ${j === i ? "opacity-100" : "pointer-events-none opacity-0"}`} aria-hidden={j !== i}>
            {m?.kind === "video" ? (
              <video
                ref={(el) => { vids.current[j] = el; }}
                key={m.url} src={m.url} poster={m.poster} muted playsInline preload={j === 0 ? "auto" : "metadata"}
                autoPlay={j === 0} loop={slides.length === 1}
                onEnded={() => j === i && slides.length > 1 && next()}
                className="absolute inset-0 h-full w-full object-cover" aria-label={m.alt}
              />
            ) : (
              <div className={`absolute inset-0 ${j === i ? "kenburns" : ""}`}>
                <ResponsiveMedia desktop={s.desktop} mobile={s.mobile} priority={j === 0} className="object-[center_30%]" />
              </div>
            )}
            <div className="absolute inset-0 bg-gradient-to-t from-black/55 via-transparent to-black/20" />
            {j === i && (
              <div className="absolute inset-x-0 bottom-0 mx-auto max-w-4xl px-6 pb-20 text-center md:pb-28">
                {s.eyebrow && <p className="eyebrow reveal text-gold-soft">{s.eyebrow}</p>}
                {s.heading && <h1 className="display reveal mt-4 text-5xl [animation-delay:.15s] md:text-7xl">{s.heading}</h1>}
                {s.text && <p className="reveal mx-auto mt-5 max-w-lg text-sm leading-7 text-ivory/80 [animation-delay:.3s] md:text-base">{s.text}</p>}
                {s.cta && s.link && <Link href={s.link} className="btn btn-ghost reveal mt-9 [animation-delay:.45s]"><span>{s.cta}</span></Link>}
              </div>
            )}
          </div>
        );
      })}
      {slides.length > 1 && (
        <div className="absolute inset-x-0 bottom-8 flex items-center justify-center gap-3">
          {slides.map((_, j) => (
            <button key={j} onClick={() => setI(j)} aria-label={`${j + 1}`} aria-current={j === i} className="h-6 w-10 py-3">
              <span className={`block h-px w-full transition ${j === i ? "bg-ivory" : "bg-ivory/35"}`} />
            </button>
          ))}
          <button onClick={() => setPaused(!paused)} aria-label={paused ? "Play" : "Pause"} className="ms-2 opacity-70 hover:opacity-100">
            {paused ? <Icon name="play" filled className="h-3.5 w-3.5" /> : <svg viewBox="0 0 24 24" className="h-3.5 w-3.5" fill="currentColor" aria-hidden><path d="M7 5h3v14H7zM14 5h3v14h-3z" /></svg>}
          </button>
        </div>
      )}
    </section>
  );
}
