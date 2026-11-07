import Link from "next/link";
import { db } from "@/lib/db";
import { json } from "@/lib/format";
import { SECTION_TYPES } from "@/lib/sections";
import { addSection, toggleSection, duplicateSection, deleteSection } from "@/app/admin/actions";
import { SortableRows } from "@/components/admin/pickers";
import { Badge, PageHead, btn, btn2, card, inp } from "@/components/admin/ui";

export default async function Homepage() {
  const secs = await db.section.findMany({ orderBy: { sort: "asc" } });
  return (
    <>
      <PageHead title="Homepage"><Link href="/" target="_blank" className={btn2}>Preview store ↗</Link></PageHead>
      <p className="-mt-4 mb-6 text-sm text-stone">Sections appear on the homepage in this order. Hidden sections are kept as drafts. Story, campaign & values blocks also appear on the “Our Story” page.</p>
      <SortableRows model="section" rows={secs.map((s) => {
        const d = json<Record<string, string>>(s.data, {});
        const title = d.titleEn || d.titleAr || (s.type === "hero" ? `${(d.slides as unknown as unknown[])?.length ?? 0} slide(s)` : "");
        return { id: s.id, node: (
          <div className="flex flex-wrap items-center gap-3">
            <Link href={`/admin/homepage/${s.id}`} className="min-w-0 flex-1"><p className="font-medium">{SECTION_TYPES[s.type]?.label ?? s.type}</p><p className="truncate text-xs text-stone">{title}</p></Link>
            {s.published ? <Badge tone="good">Live</Badge> : <Badge>Hidden</Badge>}
            <form action={toggleSection.bind(null, s.id, !s.published)}><button className={btn2}>{s.published ? "Hide" : "Publish"}</button></form>
            <Link href={`/admin/homepage/${s.id}`} className={btn2}>Edit</Link>
            <form action={duplicateSection.bind(null, s.id)}><button className={btn2}>Duplicate</button></form>
            <form action={deleteSection.bind(null, s.id)}><button className="text-sm text-red-700">Delete</button></form>
          </div>
        ) };
      })} />
      <form action={async (f: FormData) => { "use server"; await addSection(String(f.get("type"))); }} className={`${card} mt-6 flex flex-wrap items-center gap-3`}>
        <span className="text-sm font-medium">Add a section</span>
        <select name="type" className={`${inp} w-auto`}>{Object.entries(SECTION_TYPES).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}</select>
        <button className={btn}>+ Add</button>
      </form>
    </>
  );
}
