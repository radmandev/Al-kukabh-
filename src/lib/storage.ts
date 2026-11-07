import "server-only";
import { mkdir, writeFile, unlink } from "node:fs/promises";
import path from "node:path";
import { randomUUID } from "node:crypto";
import sharp from "sharp";
import { db } from "./db";

// ponytail: local-disk storage served via /media/*. Swap these two functions for S3/R2 when hosting needs it.
export const UPLOAD_DIR = path.resolve(process.env.UPLOAD_DIR || "./uploads");
const MAX = 200 * 1024 * 1024; // 200MB (videos)

const TYPES: Record<string, { ext: string; kind: "image" | "video" }> = {
  "image/jpeg": { ext: "jpg", kind: "image" },
  "image/png": { ext: "png", kind: "image" },
  "image/webp": { ext: "webp", kind: "image" },
  "image/avif": { ext: "avif", kind: "image" },
  "image/gif": { ext: "gif", kind: "image" },
  "video/mp4": { ext: "mp4", kind: "video" },
  "video/webm": { ext: "webm", kind: "video" },
  "video/quicktime": { ext: "mov", kind: "video" },
};

export async function saveUpload(file: File) {
  const t = TYPES[file.type];
  if (!t) throw new Error(`Unsupported file type: ${file.type || "unknown"}`);
  if (file.size > MAX) throw new Error("File too large (max 200MB)");
  let buf: Buffer = Buffer.from(await file.arrayBuffer());
  let width: number | undefined, height: number | undefined, ext = t.ext, mime = file.type;

  if (t.kind === "image") {
    // validates it really is an image, strips EXIF/GPS, caps size at 2800px
    const img = sharp(buf, { animated: file.type === "image/gif" }).rotate();
    const meta = await img.metadata();
    if (file.type !== "image/gif" && (meta.width ?? 0) > 2800) img.resize({ width: 2800 });
    if (file.type === "image/jpeg" || file.type === "image/png") {
      buf = await img.webp({ quality: 88 }).toBuffer();
      ext = "webp"; mime = "image/webp";
    } else buf = await img.toBuffer();
    const m2 = await sharp(buf).metadata();
    width = m2.width; height = m2.height;
  }

  const name = `${randomUUID()}.${ext}`;
  await mkdir(UPLOAD_DIR, { recursive: true });
  await writeFile(path.join(UPLOAD_DIR, name), buf);
  return db.media.create({
    data: { url: `/media/${name}`, kind: t.kind, mime, width, height, size: buf.length },
  });
}

export async function deleteMedia(id: string) {
  const m = await db.media.delete({ where: { id } });
  await unlink(path.join(UPLOAD_DIR, path.basename(m.url))).catch(() => {});
}
