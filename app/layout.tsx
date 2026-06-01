import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "OsteoCare | Gestionale Studio",
  description: "Gestionale professionale per studi di osteopatia",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="it">
      <body>{children}</body>
    </html>
  );
}
