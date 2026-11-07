// Thin-stroke icon set (1.25px) to match the fine gold linework of the logo.
const P: Record<string, string> = {
  search: "M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.35-4.35",
  user: "M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm-8 9c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5",
  bag: "M5 8h14l-1 13H6L5 8Zm4 0V6a3 3 0 0 1 6 0v2",
  heart: "M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.2a4.3 4.3 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20Z",
  menu: "M3 7h18M3 12h18M3 17h18",
  close: "M5 5l14 14M19 5 5 19",
  plus: "M12 5v14M5 12h14",
  minus: "M5 12h14",
  chevron: "m9 6 6 6-6 6",
  arrow: "M4 12h16m-6-6 6 6-6 6",
  expand: "M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5",
  play: "M8 5v14l11-7L8 5Z",
  truck: "M3 6h11v10H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z",
  check: "m5 12 5 5 9-10",
};
export function Icon({ name, className = "h-5 w-5", filled }: { name: keyof typeof P | string; className?: string; filled?: boolean }) {
  return (
    <svg viewBox="0 0 24 24" className={className} fill={filled ? "currentColor" : "none"} stroke="currentColor" strokeWidth={1.25} strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d={P[name]} />
    </svg>
  );
}
