'use client';
import Header from "@/components/Header"
import Footer from "@/components/Footer"
import Link from "next/link"
import Image from "next/image"
import { ArrowLeft } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { useState, useEffect } from 'react';

interface Project {
  id: number
  p_name: string | null
  p_desc: string | null
  date: string | null
  p_image: string | null
  p_shortdesc: string | null
}

async function fetchProjects() {
  const response = await fetch('/api/projects');
  if (!response.ok) {
    throw new Error('Failed to fetch projects');
  }
  return await response.json();
}

export default function Projects() {
  const [projects, setProjects] = useState<Project[]>([]);

  useEffect(() => {
    const loadProjects = async () => {
      try {
        const projectsData = await fetchProjects();
        setProjects(projectsData);
      } catch (error) {
        console.error('Error loading projects:', error);
      }
    };
    loadProjects();
  }, []);

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className="flex-grow">
        {/* Hero Section */}
        <section className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <div className="max-w-3xl mx-auto text-center">
              <h1 className="mb-4 text-2xl font-bold">Projelerimiz ve Referanslarımız</h1>
              <p className="text-xl mb-8">
                InData A.Ş.&apos;nin yenilikçi çözümleriyle müşterilerimize nasıl değer kattığımızı keşfedin.
              </p>
              <Button asChild variant="secondary" size="lg">
                <Link href="/" className="flex items-center">
                  <ArrowLeft className="mr-2 h-4 w-4" /> Ana Sayfaya Dön
                </Link>
              </Button>
            </div>
          </div>
        </section>

        {/* Projects Grid */}
        <section className="py-20">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {projects.map((project) => (
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
                    <p className="text-gray-600 whitespace-pre-wrap break-words mb-4">{project.p_shortdesc}</p>
                    {project.p_desc && (
                      <p className="text-gray-700 whitespace-pre-wrap break-words">{project.p_desc}</p>
                    )}
                    {project.date && (
                      <p className="text-sm text-gray-500 mt-4">Tarih: {project.date}</p>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}
