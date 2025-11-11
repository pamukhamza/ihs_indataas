"use client"

import { useState, useEffect } from "react"
import Link from "next/link"
import { Menu, X } from "lucide-react"
import Image from 'next/image';

export default function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false)
  const [isScrolled, setIsScrolled] = useState(false)

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 10)
    }
    window.addEventListener("scroll", handleScroll)
    return () => window.removeEventListener("scroll", handleScroll)
  }, [])

  const scrollToSection = (sectionId: string) => {
    setIsMenuOpen(false)
    const section = document.getElementById(sectionId)
    if (section) {
      section.scrollIntoView({ behavior: "smooth" })
    }
  }

  return (
    <header
      className={`fixed w-full z-50 transition-all duration-300 ${isScrolled ? "bg-white shadow-md" : "bg-transparent"}`}
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center py-4 md:justify-start md:space-x-10">
          <div className="flex justify-start lg:w-0 lg:flex-1">
            <Link href="/" className={`flex items-center ${isScrolled ? "text-blue-600" : "text-white"}`}>
              <Image
                src="/images/logo.png"
                alt="InData A.Ş. Logo"
                width={150}
                height={50}
                className="object-contain"
              />
            </Link>
          </div>
          <div className="-mr-2 -my-2 md:hidden">
            <button
              type="button"
              className={`rounded-md p-2 inline-flex items-center justify-center ${isScrolled ? "text-gray-400 hover:text-gray-500" : "text-white hover:text-gray-200"} hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500`}
              onClick={() => setIsMenuOpen(!isMenuOpen)}
            >
              <span className="sr-only">Menüyü aç</span>
              {isMenuOpen ? (
                <X className="h-6 w-6" aria-hidden="true" />
              ) : (
                <Menu className="h-6 w-6" aria-hidden="true" />
              )}
            </button>
          </div>
          <nav className="hidden md:flex space-x-10">
            <button
              onClick={() => scrollToSection("hakkimizda")}
              className={`text-base font-medium ${isScrolled ? "text-gray-500 hover:text-gray-900" : "text-white hover:text-gray-200"}`}
            >
              Hakkımızda
            </button>
            <button
              onClick={() => scrollToSection("cozumler")}
              className={`text-base font-medium ${isScrolled ? "text-gray-500 hover:text-gray-900" : "text-white hover:text-gray-200"}`}
            >
              Çözümler
            </button>
            <Link
              href="/projeler"
              className={`text-base font-medium ${isScrolled ? "text-gray-500 hover:text-gray-900" : "text-white hover:text-gray-200"}`}
            >
              Projeler
            </Link>
            <button
              onClick={() => scrollToSection("iletisim")}
              className={`text-base font-medium ${isScrolled ? "text-gray-500 hover:text-gray-900" : "text-white hover:text-gray-200"}`}
            >
              İletişim
            </button>
          </nav>
        </div>
      </div>

      {isMenuOpen && (
        <div className="absolute top-0 inset-x-0 p-2 transition transform origin-top-right md:hidden">
          <div className="rounded-lg shadow-lg bg-white divide-y divide-gray-100">
            <div className="pt-5 pb-6 px-5">
              <div className="flex items-center justify-between mb-4">
                <div className="h-12">
                  <Link href="/" onClick={() => setIsMenuOpen(false)}>
                    <Image
                      src="/images/logo.png"
                      alt="InData A.Ş. Logo"
                      width={120}
                      height={40}
                      className="object-contain"
                    />
                  </Link>
                </div>
                <div>
                  <button
                    type="button"
                    className="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                    onClick={() => setIsMenuOpen(false)}
                  >
                    <span className="sr-only">Menüyü kapat</span>
                    <X className="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>
              </div>
              <div className="mt-6">
                <nav className="flex flex-col space-y-4">
                  <button
                    onClick={() => scrollToSection("hakkimizda")}
                    className="flex items-center px-4 py-3 text-base font-medium text-gray-900 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors duration-200"
                  >
                    Hakkımızda
                  </button>
                  <button
                    onClick={() => scrollToSection("cozumler")}
                    className="flex items-center px-4 py-3 text-base font-medium text-gray-900 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors duration-200"
                  >
                    Çözümler
                  </button>
                  <Link 
                    href="/projeler" 
                    onClick={() => setIsMenuOpen(false)}
                    className="flex items-center px-4 py-3 text-base font-medium text-gray-900 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors duration-200"
                  >
                    Projeler
                  </Link>
                  <button
                    onClick={() => scrollToSection("iletisim")}
                    className="flex items-center px-4 py-3 text-base font-medium text-gray-900 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors duration-200"
                  >
                    İletişim
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      )}
    </header>
  )
}
