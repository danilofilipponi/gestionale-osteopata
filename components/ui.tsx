"use client";

import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, TextareaHTMLAttributes } from "react";
import { Icon, type IconName } from "./icons";

export function Button({ children, variant = "primary", icon, className = "", ...props }: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: "primary" | "secondary" | "ghost" | "danger"; icon?: IconName }) {
  const styles = { primary: "bg-sage text-white hover:bg-[#4f7f75]", secondary: "bg-white border border-line text-ink hover:bg-mist", ghost: "text-muted hover:bg-mist hover:text-ink", danger: "text-red-600 hover:bg-red-50" };
  return <button className={`inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition ${styles[variant]} ${className}`} {...props}>{icon && <Icon name={icon} size={17}/>} {children}</button>;
}

export function Input({ label, ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string }) {
  return <label className="block"><span className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted">{label}</span><input className="w-full rounded-xl border border-line bg-white px-3.5 py-3 text-sm outline-none transition focus:border-sage focus:ring-2 focus:ring-sage/10" {...props}/></label>;
}

export function TextArea({ label, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement> & { label: string }) {
  return <label className="block"><span className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted">{label}</span><textarea className="min-h-24 w-full resize-y rounded-xl border border-line bg-white px-3.5 py-3 text-sm outline-none transition focus:border-sage focus:ring-2 focus:ring-sage/10" {...props}/></label>;
}

export function Select({ label, children, ...props }: InputHTMLAttributes<HTMLSelectElement> & { label: string; children: ReactNode }) {
  return <label className="block"><span className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted">{label}</span><select className="w-full rounded-xl border border-line bg-white px-3.5 py-3 text-sm outline-none focus:border-sage" {...props}>{children}</select></label>;
}

export function Badge({ children, tone = "green" }: { children: ReactNode; tone?: "green" | "amber" | "gray" }) {
  const styles = { green: "bg-emerald-50 text-emerald-700", amber: "bg-amber-50 text-amber-700", gray: "bg-slate-100 text-slate-600" };
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${styles[tone]}`}>{children}</span>;
}

export function Empty({ icon = "file", title, text }: { icon?: IconName; title: string; text: string }) {
  return <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-line py-14 text-center"><div className="mb-3 rounded-full bg-mist p-3 text-sage"><Icon name={icon}/></div><h3 className="font-bold">{title}</h3><p className="mt-1 max-w-md text-sm text-muted">{text}</p></div>;
}
