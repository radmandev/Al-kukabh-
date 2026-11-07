import Image from "next/image";

type M = { url: string; kind: string; poster?: string; alt?: string };

/** Renders an uploaded image (optimized via next/image) or a muted autoplay video. Parent must be `relative` with a size. */
export function Media({ m, sizes = "100vw", priority, className = "", controls }: { m?: M | null; sizes?: string; priority?: boolean; className?: string; controls?: boolean }) {
  if (!m?.url) return <div className={`absolute inset-0 bg-sand ${className}`} />;
  if (m.kind === "video")
    return (
      <video
        className={`absolute inset-0 h-full w-full object-cover ${className}`}
        src={m.url} poster={m.poster} muted loop playsInline autoPlay={!controls} controls={controls}
        preload={priority ? "auto" : "metadata"} aria-label={m.alt}
      />
    );
  return <Image src={m.url} alt={m.alt ?? ""} fill sizes={sizes} priority={priority} className={`object-cover ${className}`} />;
}

/** Desktop + optional mobile art direction */
export function ResponsiveMedia({ desktop, mobile, ...rest }: { desktop?: M | null; mobile?: M | null; sizes?: string; priority?: boolean; className?: string }) {
  if (!mobile?.url) return <Media m={desktop} {...rest} />;
  return (
    <>
      <div className="absolute inset-0 hidden md:block"><Media m={desktop} {...rest} /></div>
      <div className="absolute inset-0 md:hidden"><Media m={mobile} {...rest} /></div>
    </>
  );
}
