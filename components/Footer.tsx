export default function Footer() {
  const currentYear = new Date().getFullYear()
  
  return (
    <footer className="bg-gray-800 text-white py-8">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row justify-between items-center">
          <div className="mb-4 md:mb-0">
            <p>&copy; {currentYear} InData A.Ş. Tüm hakları saklıdır.</p>
            
          </div>
          <div className="flex space-x-4">
            <a
              href="https://logsoft.com.tr"
              target="_blank"
              rel="follow referrer"
              className="hover:text-blue-400 transition duration-300 ms-5"
            >
              Logsoft Yazılım tarafından geliştirildi.
            </a>
          </div>
        </div>
      </div>
    </footer>
  )
}
