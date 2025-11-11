import Header from "@/components/Header"
import Footer from "@/components/Footer"
import Link from "next/link"
import Image from "next/image"
import {
  ArrowRight,
  Mail,
  Phone,
  MapPin,
} from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { prisma } from "@/lib/prisma"

interface Project {
  id: number
  p_name: string | null
  p_desc: string | null
  date: string | null
  p_image: string | null
  p_shortdesc: string | null
}

interface Solution {
  image: string
  title: string
  description: string
}

const solutions: Solution[] = [
  { image: "/images/isotope-1_.jpg", title: "Isıtma, Havalandırma, Klima Soğutma Sistemleri", description: "Enerji verimli iklimlendirme çözümleri ve konfor sistemleri" },
  { image: "/images/isotope-2_.jpg", title: "Yangın ve Yangından Korunma Sistemleri", description: "Modern yangın algılama ve söndürme sistemleri" },
  { image: "/images/isotope-3_.jpg", title: "Sıhhi Tesisat Sistemleri", description: "Yüksek kaliteli su ve atık su tesisatı çözümleri" },
  { image: "/images/isotope-4_.jpg", title: "Otomasyon Sistemleri", description: "Akıllı bina ve endüstriyel otomasyon sistemleri" },
  { image: "/images/isotope-4_.jpg", title: "Mekanik Elektrik Sistemleri", description: "Güvenilir elektrik altyapısı ve mekanik sistem entegrasyonu" },
  { image: "/images/isotope-9_.jpg", title: "İnşaat", description: "Modern yapı ve tesis inşaat projeleri" },
  { image: "/images/isotope-10_.jpg", title: "Mimari Tasarım", description: "Fonksiyonel ve estetik mimari çözümler" },
  { image: "/images/isotope-8_.jpg", title: "Basınçlı Hava Sistemleri", description: "Verimli basınçlı hava üretim ve dağıtım sistemleri" },
  { image: "/images/isotope-5_.jpg", title: "Filtreleme Sistemleri Toz Toplama Sistemleri", description: "Endüstriyel hava filtreleme ve toz toplama çözümleri" },
  { image: "/images/isotope-6_.jpg", title: "Endüstriyel Mekanik Montaj ve Borulama Sistemleri", description: "Profesyonel endüstriyel tesisat ve montaj hizmetleri" },
  { image: "/images/isotope-7_.jpg", title: "Teknolojik Tesisat Sistemleri", description: "Yenilikçi ve akıllı tesisat çözümleri" },
  { image: "/images/isotope-8_.jpg", title: "Montajı Evoperatif Soğutma Sistemleri", description: "Enerji tasarruflu evaporatif soğutma çözümleri" },
  { image: "/images/isotope-1_.jpg", title: "Su Tasfiye ve Arıtma Sistemleri", description: "Endüstriyel ve ticari su arıtma sistemleri" },
  { image: "/images/isotope-2_.jpg", title: "Tıbbi Gaz ve Steril Saha Sistemleri", description: "Hastane ve laboratuvar gaz sistemleri" },
  { image: "/images/sistem-entegrasyon.png", title: "Sistem Entegrasyon Çözümleri", description: "Kapsamlı sistem entegrasyonu ve optimizasyonu" },
  { image: "/images/isotope-1_.jpg", title: "Network Çözümleri", description: "Güvenli ve hızlı ağ altyapı çözümleri" },
  { image: "/images/isotope-5_.jpg", title: "Altyapı Kablolama Çözümleri", description: "Profesyonel yapısal kablolama sistemleri" },
  { image: "/images/isotope-6_.jpg", title: "Anahtar Teslim Projeler", description: "Komple proje tasarım ve uygulama hizmetleri" },
  { image: "/images/isotope-2_.jpg", title: "Veri Merkezi Tasarımı ve Çözümleri", description: "Modern veri merkezi altyapı çözümleri" },
  { image: "/images/isotope-2_.jpg", title: "Veri Merkezi Altyapı Çözümleri", description: "Güvenilir ve verimli veri merkezi altyapıları" },
  { image: "/images/isotope-9_.jpg", title: "Taahhüt proje uygulama işletme sonrası bakım hizmetlerinin yapımı", description: "Tamamlayıcı bakım ve destek hizmetleri" },
]

async function getProjects() {
  try {
    const projects = await prisma.indata_projects.findMany({
      take: 3,
      orderBy: {
        id: 'desc'
      }
    });
    return projects;
  } catch (error) {
    console.error('Error fetching projects:', error);
    return [];
  }
}

export default async function Home() {
  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-grow">
        {/* Hero Section */}
        <section className="relative bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white overflow-hidden">
          <div className="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]" />
          <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-24 md:py-32">
            <div className="flex flex-col md:flex-row items-center">
              <div className="md:w-1/2 mb-8 md:mb-0">
                <h1 className="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-6 leading-tight animate-fade-in-up">
                  Kapsamlı Mühendislik <br />
                  <span className="text-blue-300">ve Teknoloji Çözümleri</span>
                </h1>
                <p className="text-xl mb-8 text-blue-100 max-w-2xl animate-fade-in-up animation-delay-200">
                Endüstriyel tesisler, veri merkezleri ve bina sistemleri için en son teknoloji çözümlerle işletmenizi geleceğe taşıyın.
                </p>
                <div className="flex flex-col sm:flex-row gap-4 animate-fade-in-up animation-delay-400">
                  <Button asChild size="lg" className="font-semibold text-lg">
                    <Link href="/#cozumler">Çözümlerimizi Keşfedin</Link>
                  </Button>
                  <Button asChild size="lg" className="font-semibold text-lg">
                    <Link href="/#iletisim">Bize Ulaşın</Link>
                  </Button>
                </div>
              </div>
              <div className="md:w-1/2 relative animate-fade-in-up animation-delay-600">
                <Image
                  src="/images/slide-bg-1.jpg?height=400&width=600"
                  alt="Endüstriyel Çözümler"
                  width={600}
                  height={400}
                  className="rounded-lg shadow-2xl"
                />
                <div className="absolute -bottom-4 -right-4 bg-white text-blue-600 p-4 rounded-lg shadow-lg">
                  <p className="font-bold text-2xl">20+</p>
                  <p className="text-sm">Uzmanlık Alanı</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Solutions Section */}
        <section id="cozumler" className="py-20">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <h2 className="text-3xl font-bold mb-4 text-center">Çözümlerimiz</h2>
            <p className="text-xl text-gray-600 mb-12 text-center max-w-3xl mx-auto">
              Endüstriyel tesisler, veri merkezleri ve bina sistemleri için kapsamlı mühendislik ve teknoloji
              çözümlerimiz.
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {solutions.map((solution, index) => (
                <Card key={index} className="transition-all duration-300 hover:shadow-lg hover:-translate-y-1 overflow-hidden">
                  <CardHeader className="p-0">
                    <div className="relative w-full h-48 overflow-hidden">
                      <Image
                        src={solution.image}
                        alt={solution.title}
                        fill
                        className="object-cover transition-transform duration-300 hover:scale-110"
                      />
                    </div>
                    <CardTitle className="text-xl p-6">{solution.title}</CardTitle>
                  </CardHeader>
                  <CardContent className="px-6 pb-6">
                    <p className="text-gray-600">{solution.description}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>

        {/* Projects Section */}
        <section id="projeler" className="py-20 bg-gray-50">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <h2 className="text-3xl font-bold mb-4 text-center">Projelerimiz</h2>
            <p className="text-xl text-gray-600 mb-12 text-center max-w-3xl mx-auto">
              Müşterilerimize sunduğumuz yenilikçi çözümlerle elde ettiğimiz başarı hikayeleri.
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {(await getProjects()).map((project: Project) => (
                <Card key={project.id} className="transition-all duration-300 hover:shadow-lg hover:-translate-y-1 overflow-hidden h-full flex flex-col">
                  <CardHeader className="p-0">
                    <div className="relative w-full h-48 overflow-hidden">
                      <Image
                        src={project.p_image 
                          ? `https://noktanet.s3.eu-central-1.amazonaws.com/uploads/images/indata_projeler/${project.p_image}`
                          : 'https://noktanet.s3.eu-central-1.amazonaws.com/uploads/images/indata_projeler/gorsel_hazirlaniyor.jpg'}
                        alt={project.p_name || 'Proje Görseli'}
                        fill
                        className="object-cover transition-transform duration-300 hover:scale-110"
                      />
                    </div>
                    <CardTitle className="text-xl p-6 border-b">{project.p_name}</CardTitle>
                  </CardHeader>
                  <CardContent className="px-6 py-4 flex-1">
                    <p className="text-gray-600 whitespace-pre-wrap break-words">{project.p_shortdesc}</p>
                  </CardContent>
                </Card>
              ))}
            </div>
            <div className="text-center mt-12">
              <Button asChild size="lg">
                <Link href="/projeler">Tüm Projelerimizi Görüntüle</Link>
              </Button>
            </div>
          </div>
        </section>

        {/* About Section */}
        <section id="hakkimizda" className="py-20 bg-gray-50">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <h2 className="text-3xl font-bold mb-12 text-center">Hakkımızda</h2>
            <div className="flex flex-col md:flex-row items-center">
              <div className="md:w-1/2 mb-8 md:mb-0">
                <Image
                  src="/images/slide-bg-1.jpg?height=400&width=600"
                  alt="InDataas Ekibi"
                  width={600}
                  height={400}
                  className="rounded-lg shadow-lg"
                />
              </div>
              <div className="md:w-1/2 md:pl-12">
                <p className="text-gray-700 mb-6 text-lg">
                  InData A.Ş. endüstriyel tesisler, veri merkezleri ve bina sistemleri için kapsamlı mühendislik ve
                  teknoloji çözümleri sunan lider bir şirkettir. Deneyimli ekibimiz ve yenilikçi yaklaşımımızla,
                  müşterilerimizin karmaşık projelerini başarıyla hayata geçiriyoruz.
                </p>
                <p className="text-gray-700 mb-8 text-lg">
                  Misyonumuz, en son teknolojileri kullanarak müşterilerimizin operasyonel verimliliğini artırmak,
                  enerji tasarrufu sağlamak ve sürdürülebilir çözümler sunmaktır.
                </p>
                <Button asChild variant="outline" size="lg">
                  <Link href="/projeler" className="flex items-center">
                    Projelerimizi İnceleyin <ArrowRight className="ml-2 h-5 w-5" />
                  </Link>
                </Button>
              </div>
            </div>
          </div>
        </section>

        {/* Contact Section with Map */}
        <section id="iletisim" className="py-20 bg-gray-50">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center max-w-3xl mx-auto mb-16">
              <h2 className="text-4xl font-bold mb-6 bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                İletişim
              </h2>
              <p className="text-xl text-gray-600">
                Projeleriniz için özel çözümler sunmak üzere sizinle iletişime geçmek için sabırsızlanıyoruz.
              </p>
            </div>
            
            <div className="flex flex-col lg:flex-row gap-12 items-stretch">
              <div className="lg:w-1/2">
                <div className="bg-white rounded-2xl shadow-xl p-8 h-full">
                  <h3 className="text-2xl font-bold mb-8 text-gray-800">Bize Ulaşın</h3>
                  <div className="space-y-6">
                    <div className="flex items-center p-4 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                      <div className="bg-blue-100 p-3 rounded-full">
                        <Mail className="h-6 w-6 text-blue-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm text-gray-500">E-posta</p>
                        <a href="mailto:info@indataas.com" className="text-gray-800 hover:text-blue-600 transition-colors">
                          info@indataas.com
                        </a>
                      </div>
                    </div>
                    
                    <div className="flex items-center p-4 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                      <div className="bg-blue-100 p-3 rounded-full">
                        <Phone className="h-6 w-6 text-blue-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm text-gray-500">Ankara Telefon</p>
                        <a href="tel:+903123970075" className="text-gray-800 hover:text-blue-600 transition-colors">
                          +90 (312) 397 00 75
                        </a>
                      </div>
                    </div>

                    <div className="flex items-center p-4 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                      <div className="bg-blue-100 p-3 rounded-full">
                        <Phone className="h-6 w-6 text-blue-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm text-gray-500">İstanbul Telefon</p>
                        <a href="tel:+902122228780" className="text-gray-800 hover:text-blue-600 transition-colors">
                          +90 (212) 222 87 80
                        </a>
                      </div>
                    </div>

                    <div className="flex items-center p-4 hover:bg-blue-50 rounded-lg transition-colors duration-200">
                      <div className="bg-blue-100 p-3 rounded-full">
                        <MapPin className="h-6 w-6 text-blue-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm text-gray-500">Adres</p>
                        <p className="text-gray-800">
                          Çamlıca Mah. Anadolu Bulvarı No:20E İç Kapı No:4 Yenimahalle, Ankara
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="lg:w-1/2">
                <div className="bg-white rounded-2xl shadow-xl overflow-hidden h-full">
                  <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6117.091402764079!2d32.772000194356075!3d39.951550457292015!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14d34903a9bba683%3A0x749bdf83360f894b!2sNokta%20Elektronik%20-%20Ankara!5e0!3m2!1str!2str!4v1737459225738!5m2!1str!2str"
                    width="100%"
                    height="100%"
                    style={{ border: 0, minHeight: "500px" }}
                    loading="lazy"
                    referrerPolicy="no-referrer-when-downgrade"
                    className="w-full h-full"
                  ></iframe>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  )
}
