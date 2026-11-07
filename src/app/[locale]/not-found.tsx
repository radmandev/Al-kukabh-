import Link from "next/link";
export default function NotFound() {
  return (
    <div className="py-40 text-center">
      <p className="display text-6xl text-gold">404</p>
      <p className="mt-4 text-stone">الصفحة غير موجودة · Page not found</p>
      <Link href="/" className="btn btn-dark mt-10">الرئيسية · Home</Link>
    </div>
  );
}
