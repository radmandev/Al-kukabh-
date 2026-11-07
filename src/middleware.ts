import { NextResponse, type NextRequest } from "next/server";

// /anything -> /ar/anything (or the visitor's saved language)
export function middleware(req: NextRequest) {
  const lang = req.cookies.get("lang")?.value === "en" ? "en" : "ar";
  const url = req.nextUrl.clone();
  url.pathname = `/${lang}${req.nextUrl.pathname === "/" ? "" : req.nextUrl.pathname}`;
  return NextResponse.redirect(url);
}

export const config = {
  matcher: ["/((?!ar(?:/|$)|en(?:/|$)|admin|api|media|_next|brand|favicon|robots|sitemap).*)"],
};
