import { Cormorant_Garamond, Jost, Amiri, IBM_Plex_Sans_Arabic } from "next/font/google";

const displayEn = Cormorant_Garamond({ subsets: ["latin"], weight: ["300", "400", "500"], style: ["normal", "italic"], variable: "--f-display-en" });
const sansEn = Jost({ subsets: ["latin"], weight: ["300", "400", "500"], variable: "--f-sans-en" });
const displayAr = Amiri({ subsets: ["arabic"], weight: ["400", "700"], variable: "--f-display-ar" });
const sansAr = IBM_Plex_Sans_Arabic({ subsets: ["arabic"], weight: ["300", "400", "500"], variable: "--f-sans-ar" });

export const fontVars = [displayEn, sansEn, displayAr, sansAr].map((f) => f.variable).join(" ");
