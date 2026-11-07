// Dev/demo seed. Safe to re-run: wipes catalog/content (NOT orders/users) and recreates demo data.
// Usage: npm run seed
import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";
import sharp from "sharp";
import { mkdir, writeFile } from "node:fs/promises";
import { randomUUID } from "node:crypto";
import path from "node:path";

const db = new PrismaClient();
const DIR = path.resolve(process.env.UPLOAD_DIR || "./uploads");
const TONES = [["#18181b", "#2a2a2f"], ["#e9e2d4", "#d8cdb8"], ["#3b3530", "#5a5047"], ["#f3efe7", "#e2d9c8"], ["#1f2421", "#353d38"], ["#6b5d4f", "#8a7a68"]];

// Brand-toned placeholder: gradient + gold line-art abaya silhouette (replace via Admin → Media)
async function placeholder(w, h, tone, label) {
  const [a, b] = TONES[tone % TONES.length];
  const dark = tone % 2 === 0;
  const gold = dark ? "#d6bd8a" : "#a8844c";
  const s = Math.min(w, h) / 100;
  const cx = w / 2, top = h * 0.14;
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
  <defs><radialGradient id="g" cx="50%" cy="35%" r="75%"><stop offset="0" stop-color="${b}"/><stop offset="1" stop-color="${a}"/></radialGradient></defs>
  <rect width="100%" height="100%" fill="url(#g)"/>
  <g transform="translate(${cx - 50 * s * 0.8},${top}) scale(${s * 0.8})" fill="none" stroke="${gold}" stroke-width="${0.35}" stroke-linecap="round" stroke-linejoin="round" opacity=".9">
    <path d="M50 4c-4 0-7 3.5-7 8 0 3 1 5 2.5 6.5"/><path d="M50 4c4 0 7 3.5 7 8 0 3-1 5-2.5 6.5"/>
    <path d="M45 18c-3 2-6 6-7 12l-9 60c8 3 16 4 21 4s13-1 21-4l-9-60c-1-6-4-10-7-12"/>
    <path d="M43 34c-4 16-8 34-13 54"/><path d="M57 34c3 18 6 36 12 52"/><path d="M50 22v72"/>
  </g>
  <text x="50%" y="${h - 60}" text-anchor="middle" font-family="Georgia,serif" font-size="${Math.round(h / 40)}" letter-spacing="6" fill="${gold}" opacity=".75">${label}</text>
  </svg>`;
  const buf = await sharp(Buffer.from(svg)).webp({ quality: 82 }).toBuffer();
  const name = `${randomUUID()}.webp`;
  await writeFile(path.join(DIR, name), buf);
  return db.media.create({ data: { url: `/media/${name}`, kind: "image", mime: "image/webp", width: w, height: h, size: buf.length, altAr: "صورة توضيحية", altEn: "Placeholder image" } });
}
const ref = (m) => ({ id: m.id, url: m.url, kind: m.kind, altAr: m.altAr, altEn: m.altEn });

async function main() {
  await mkdir(DIR, { recursive: true });
  const email = (process.env.ADMIN_EMAIL || "admin@alkawkaba.com").toLowerCase();
  await db.user.upsert({ where: { email }, update: {}, create: { email, name: "Store Owner", role: "ADMIN", passwordHash: await bcrypt.hash(process.env.ADMIN_PASSWORD || "ChangeMe123!", 12) } });

  await db.section.deleteMany(); await db.productMedia.deleteMany(); await db.product.deleteMany();
  await db.collection.deleteMany(); await db.category.deleteMany(); await db.media.deleteMany();

  const cats = {};
  for (const [i, [slug, nameAr, nameEn]] of [["occasion", "عبايات المناسبات", "Occasion"], ["everyday", "عبايات يومية", "Everyday"], ["wedding", "الأعراس", "Weddings"], ["evening", "السهرات", "Evening"]].entries())
    cats[slug] = await db.category.create({ data: { slug, nameAr, nameEn, sort: i } });

  const colDefs = [
    ["noor", "مجموعة نور", "The Noor Collection", "قصّات منسابة بلمسات ذهبية خافتة، مستوحاة من ضوء الفجر.", "Fluid silhouettes touched with soft gold, inspired by first light."],
    ["layali", "ليالي", "Layali", "عبايات السهرة بتطريز يدوي وأقمشة فاخرة.", "Evening abayas with hand embroidery and rich fabrics."],
    ["essentials", "الأساسيات", "Essentials", "قطع يومية بخامات ناعمة وتفصيل متقن.", "Everyday pieces in soft fabrics, impeccably tailored."],
    ["atelier", "إصدارات الأتيليه", "Atelier Editions", "قطع محدودة العدد تُصنع يدوياً في مشغلنا.", "Limited pieces, handmade in our atelier."],
  ];
  const cols = [];
  for (const [i, [slug, nameAr, nameEn, descAr, descEn]] of colDefs.entries()) {
    const cover = await placeholder(2000, 1300, i * 2, nameEn.toUpperCase());
    cols.push(await db.collection.create({ data: { slug, nameAr, nameEn, descAr, descEn, coverId: cover.id, sort: i } }));
  }

  const names = [
    ["عباية ضياء", "Diya Abaya"], ["عباية سَحَر", "Sahar Abaya"], ["عباية لؤلؤ", "Lulu Abaya"], ["عباية مِسك", "Misk Abaya"],
    ["عباية ريم", "Reem Abaya"], ["عباية غيم", "Ghaym Abaya"], ["عباية جُمان", "Juman Abaya"], ["عباية ياسمين", "Yasmin Abaya"],
    ["عباية الكوكبة الذهبية", "Golden Kawkaba Abaya"], ["عباية شروق", "Shurooq Abaya"], ["عباية نسيم", "Naseem Abaya"], ["عباية ليان", "Layan Abaya"],
  ];
  const products = [];
  for (const [i, [nameAr, nameEn]] of names.entries()) {
    const imgs = [await placeholder(1200, 1600, i, nameEn.toUpperCase()), await placeholder(1200, 1600, i + 1, "DETAIL"), await placeholder(1200, 1600, i + 3, "BACK")];
    const col = cols[i % 4];
    const cat = [cats.occasion, cats.everyday, cats.evening, cats.wedding][i % 4];
    const price = [890, 1250, 650, 1480, 720, 980, 1890, 560, 2400, 840, 690, 1150][i];
    products.push(await db.product.create({
      data: {
        slug: nameEn.toLowerCase().replace(/\s+/g, "-"), sku: `KW-${String(i + 1).padStart(3, "0")}`, nameAr, nameEn,
        descAr: "عباية مصممة بعناية من قماش فاخر ينساب بخفة، بقصة واسعة أنيقة وتفاصيل يدوية دقيقة على الأكمام.",
        descEn: "A carefully composed abaya in a fluid luxury fabric, with a relaxed elegant cut and fine hand-finished details at the sleeves.",
        fabricAr: "كريب ياباني فاخر · بطانة حرير", fabricEn: "Premium Japanese crepe · silk lining",
        detailsAr: "قصة واسعة\nتطريز يدوي على الأكمام\nتأتي مع طرحة مطابقة", detailsEn: "Relaxed fit\nHand embroidery at the cuffs\nMatching shayla included",
        careAr: "تنظيف جاف فقط. يُكوى على حرارة منخفضة.", careEn: "Dry clean only. Iron on low heat.",
        deliveryAr: i % 3 === 0 ? "تُشحن خلال 24 ساعة" : "", deliveryEn: i % 3 === 0 ? "Ships within 24 hours" : "",
        price, salePrice: i === 3 ? 1190 : i === 7 ? 450 : null,
        sizes: JSON.stringify(["52", "54", "56", "58", "60"]),
        colors: JSON.stringify(i % 2 ? [{ nameAr: "أسود", nameEn: "Black", hex: "#0e0e10" }, { nameAr: "رملي", nameEn: "Sand", hex: "#d8cdb8" }] : [{ nameAr: "أسود", nameEn: "Black", hex: "#0e0e10" }]),
        stock: i === 5 ? 2 : 12, availability: i === 6 || i === 8 ? "made_to_order" : "in_stock",
        isNew: i < 6, isFeatured: i % 3 === 1, isLimited: i === 6 || i === 8, isReady: i % 3 === 0, categoryId: cat.id, collectionId: col.id,
        media: { create: imgs.map((m, sort) => ({ mediaId: m.id, sort })) },
      },
    }));
  }

  const hero1 = await placeholder(2400, 1350, 0, "CAMPAIGN · SS27"), hero1m = await placeholder(1080, 1920, 0, "CAMPAIGN");
  const hero2 = await placeholder(2400, 1350, 2, "LAYALI"), occ = [];
  for (const [i, l] of ["EVENING", "DAILY", "WEDDINGS", "EXCLUSIVE"].entries()) occ.push(await placeholder(1000, 1500, i * 2 + 1, l));
  const atelier = await placeholder(1400, 1750, 4, "THE ATELIER"), det = [];
  for (const [i, l] of ["FABRIC", "EMBROIDERY", "TAILORING", "FINISHING"].entries()) det.push(await placeholder(900, 900, i + 1, l));
  const camp = await placeholder(2400, 1400, 2, "EDITORIAL");

  const sections = [
    ["hero", { slides: [
      { media: ref(hero1), mobileMedia: ref(hero1m), eyebrowAr: "المجموعة الجديدة", eyebrowEn: "New Collection", headingAr: "أناقة تُروى بهدوء", headingEn: "Elegance, Quietly Told", textAr: "قطع خالدة صُممت لتليق بكِ في كل لحظة.", textEn: "Timeless pieces composed for every moment of yours.", ctaAr: "اكتشفي المجموعة", ctaEn: "Discover the Collection", link: "/ar/collections/noor" },
      { media: ref(hero2), eyebrowAr: "ليالي", eyebrowEn: "Layali", headingAr: "لسهراتكِ الاستثنائية", headingEn: "For Extraordinary Evenings", ctaAr: "تسوقي الآن", ctaEn: "Shop Now", link: "/ar/collections/layali" },
    ] }],
    ["products", { titleAr: "وصل حديثاً", titleEn: "New Arrivals", source: "new", limit: 8, ctaAr: "عرض الكل", ctaEn: "View All", link: "/ar/shop?tag=new" }],
    ["collections", { titleAr: "المجموعات", titleEn: "The Collections", collectionIds: [] }],
    ["occasions", { titleAr: "تسوقي حسب المناسبة", titleEn: "Shop by Occasion", items: [
      { media: ref(occ[0]), labelAr: "السهرات والمناسبات", labelEn: "Evening & Events", link: "/ar/shop?category=evening" },
      { media: ref(occ[1]), labelAr: "إطلالات يومية", labelEn: "Daily Wear", link: "/ar/shop?category=everyday" },
      { media: ref(occ[2]), labelAr: "الأعراس", labelEn: "Weddings", link: "/ar/shop?category=wedding" },
      { media: ref(occ[3]), labelAr: "قطع حصرية", labelEn: "Exclusive Pieces", link: "/ar/shop?tag=limited" },
    ] }],
    ["ready", { titleAr: "جاهزة للارتداء", titleEn: "Ready to Wear", textAr: "قطع متوفرة تُشحن إليكِ فوراً.", textEn: "Available now, shipped to you right away.", limit: 4, ctaAr: "تسوقي الجاهز", ctaEn: "Shop Ready to Wear", link: "/ar/shop?tag=ready" }],
    ["story", { eyebrowAr: "الأتيليه", eyebrowEn: "The Atelier", titleAr: "حيث تولد كل قطعة", titleEn: "Where Every Piece Begins", textAr: "في مشغلنا، تمر كل عباية بأيدٍ خبيرة — من اختيار القماش إلى آخر غرزة تطريز. نؤمن أن الفخامة الحقيقية تكمن في التفاصيل التي لا تُرى.", textEn: "In our atelier, every abaya passes through expert hands — from selecting the fabric to the final embroidered stitch. We believe true luxury lives in the details you don't see.", media: ref(atelier), gallery: det.map((m, i) => ({ media: ref(m), captionAr: ["الأقمشة", "التطريز", "التفصيل", "اللمسات الأخيرة"][i], captionEn: ["Fabric", "Embroidery", "Tailoring", "Finishing"][i] })), ctaAr: "قصتنا", ctaEn: "Our Story", link: "/ar/story" }],
    ["campaign", { eyebrowAr: "حملة الموسم", eyebrowEn: "Seasonal Campaign", titleAr: "ضوء الكوكبة", titleEn: "Light of Al Kawkaba", layout: "full", items: [{ media: ref(camp) }], ctaAr: "اكتشفي", ctaEn: "Discover", link: "/ar/collections" }],
    ["values", { titleAr: "وعدنا لكِ", titleEn: "Our Promise", items: [
      { titleAr: "حِرفة متقنة", titleEn: "Premium Craftsmanship", textAr: "كل قطعة تُصنع بأيدٍ خبيرة.", textEn: "Every piece made by expert hands." },
      { titleAr: "تصاميم حصرية", titleEn: "Exclusive Designs", textAr: "تصاميم أصلية لا تتكرر.", textEn: "Original designs, never repeated." },
      { titleAr: "أقمشة فاخرة", titleEn: "Quality Fabrics", textAr: "خامات مختارة من أرقى المصادر.", textEn: "Fabrics sourced from the finest mills." },
      { titleAr: "تفصيل أنيق", titleEn: "Elegant Tailoring", textAr: "قصّات مدروسة تمنحكِ الراحة والأناقة.", textEn: "Considered cuts for comfort and grace." },
      { titleAr: "تسوق آمن", titleEn: "Secure Shopping", textAr: "دفع آمن وخصوصية تامة.", textEn: "Secure payment, complete privacy." },
      { titleAr: "خدمة وتوصيل", titleEn: "Delivery & Care", textAr: "توصيل سريع وخدمة عملاء بانتظاركِ.", textEn: "Fast delivery and attentive client care." },
    ] }],
  ];
  for (const [i, [type, data]] of sections.entries()) await db.section.create({ data: { type, sort: i, data: JSON.stringify(data) } });

  await db.discount.upsert({ where: { code: "WELCOME10" }, update: {}, create: { code: "WELCOME10", type: "percent", value: 10 } });
  console.log(`Seeded ${products.length} products, ${cols.length} collections, ${sections.length} sections. Admin: ${email}`);
}
main().finally(() => db.$disconnect());
