// One schema per homepage block type. The admin editor renders forms from it,
// the storefront reads the same keys. Add a type here + a renderer in components/sections.
export type Field =
  | { key: string; label: string; kind: "text" | "textarea" | "link" | "number" | "media" | "products" | "collections" | "bool" }
  | { key: string; label: string; kind: "select"; options: string[] }
  | { key: string; label: string; kind: "list"; fields: Field[] };

const bi = (key: string, label: string, kind: "text" | "textarea" = "text"): Field[] => [
  { key: key + "Ar", label: label + " (AR)", kind },
  { key: key + "En", label: label + " (EN)", kind },
];
const cta: Field[] = [...bi("cta", "Button text"), { key: "link", label: "Button link", kind: "link" }];

export const SECTION_TYPES: Record<string, { label: string; fields: Field[] }> = {
  hero: {
    label: "Hero slideshow",
    fields: [{
      key: "slides", label: "Slides", kind: "list", fields: [
        { key: "media", label: "Desktop image / video", kind: "media" },
        { key: "mobileMedia", label: "Mobile image / video (optional)", kind: "media" },
        ...bi("eyebrow", "Small label"), ...bi("heading", "Headline"), ...bi("text", "Text", "textarea"), ...cta,
      ],
    }],
  },
  collections: {
    label: "Featured collections (editorial)",
    fields: [...bi("title", "Section title"), { key: "collectionIds", label: "Collections (empty = all published)", kind: "collections" }],
  },
  products: {
    label: "Product carousel",
    fields: [
      ...bi("title", "Section title"),
      { key: "source", label: "Products from", kind: "select", options: ["new", "featured", "limited", "ready", "manual"] },
      { key: "productIds", label: "Products (when source = manual)", kind: "products" },
      { key: "limit", label: "Max products", kind: "number" }, ...cta,
    ],
  },
  occasions: {
    label: "Shop by occasion",
    fields: [...bi("title", "Section title"), {
      key: "items", label: "Tiles", kind: "list",
      fields: [{ key: "media", label: "Image / video", kind: "media" }, ...bi("label", "Label"), { key: "link", label: "Link", kind: "link" }],
    }],
  },
  ready: {
    label: "Ready to wear",
    fields: [...bi("title", "Section title"), ...bi("text", "Intro", "textarea"), { key: "limit", label: "Max products", kind: "number" }, ...cta],
  },
  story: {
    label: "Brand story / Atelier",
    fields: [
      ...bi("eyebrow", "Small label"), ...bi("title", "Title"), ...bi("text", "Text", "textarea"),
      { key: "media", label: "Main image / video", kind: "media" },
      { key: "gallery", label: "Detail images", kind: "list", fields: [{ key: "media", label: "Image / video", kind: "media" }, ...bi("caption", "Caption")] },
      ...cta,
    ],
  },
  campaign: {
    label: "Editorial campaign",
    fields: [
      ...bi("eyebrow", "Small label"), ...bi("title", "Title"), ...bi("text", "Text", "textarea"),
      { key: "layout", label: "Layout", kind: "select", options: ["full", "split", "grid"] },
      { key: "items", label: "Media", kind: "list", fields: [{ key: "media", label: "Image / video", kind: "media" }, ...bi("caption", "Caption")] },
      ...cta,
    ],
  },
  values: {
    label: "Brand values",
    fields: [...bi("title", "Section title"), { key: "items", label: "Values", kind: "list", fields: [...bi("title", "Title"), ...bi("text", "Text", "textarea")] }],
  },
  text: {
    label: "Text / statement",
    fields: [...bi("eyebrow", "Small label"), ...bi("title", "Title"), ...bi("text", "Text", "textarea"), ...cta],
  },
};

/** media fields store {url, kind, poster?, alt?} so rendering needs no lookup */
export type MediaRef = { id?: string; url: string; kind: string; poster?: string; altAr?: string; altEn?: string };
