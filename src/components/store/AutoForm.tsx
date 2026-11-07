"use client";
/** GET form that re-submits whenever a control changes (native filtering, no JS state). */
export function AutoForm(props: React.FormHTMLAttributes<HTMLFormElement>) {
  return <form {...props} onChange={(e) => e.currentTarget.requestSubmit()} />;
}
