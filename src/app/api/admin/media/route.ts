import { NextResponse } from "next/server";
import { currentUser, STAFF } from "@/lib/auth";
import { db } from "@/lib/db";
import { saveUpload } from "@/lib/storage";

const staff = async () => { const u = await currentUser(); return u && STAFF.includes(u.role as never) ? u : null; };

export async function GET(req: Request) {
  if (!(await staff())) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  const kind = new URL(req.url).searchParams.get("kind");
  const media = await db.media.findMany({ where: kind ? { kind } : {}, orderBy: { createdAt: "desc" }, take: 500 });
  return NextResponse.json(media);
}

export async function POST(req: Request) {
  if (!(await staff())) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  const files = (await req.formData()).getAll("file").filter((f): f is File => f instanceof File);
  const out = [], errors = [];
  for (const f of files) {
    try { out.push(await saveUpload(f)); } catch (e) { errors.push(`${f.name}: ${(e as Error).message}`); }
  }
  return NextResponse.json({ media: out, errors }, { status: out.length ? 200 : 400 });
}
