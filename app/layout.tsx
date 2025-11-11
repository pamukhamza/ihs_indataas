import "./globals.css"
import type { Metadata } from "next"
import { Poppins } from "next/font/google"

const poppins = Poppins({
  weight: ["300", "400", "500", "600", "700"],
  subsets: ["latin"],
  display: "swap",
})

export const metadata: Metadata = {
  title: "InData A.Ş. - Mühendislik ve Teknoloji Çözümleri",
  description: "InData A.Ş. işletmelere özel kapsamlı mühendislik ve teknoloji çözümleri sunan lider teknoloji şirketidir.",
  icons: {
    icon: '/favicon.png',
  },
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="tr" className="scroll-smooth">
      <body className={poppins.className}>{children}</body>
    </html>
  )
}