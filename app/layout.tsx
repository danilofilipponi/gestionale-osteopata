import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Danilo Filipponi | Gestionale Studio",
  description: "Gestionale professionale per studio di riabilitazione e osteopatia",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="it">
      <body>{children}</body>
    </html>
  );
}
