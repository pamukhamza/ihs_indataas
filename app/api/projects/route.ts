import { NextResponse } from 'next/server';
import { prisma } from '@/lib/prisma';

export async function GET() {
  try {
    const projects = await prisma.$queryRaw`
      SELECT id, p_name, p_desc, date, p_image, p_shortdesc 
      FROM indata_projects 
      ORDER BY id DESC
    `;
    return NextResponse.json(projects);
  } catch (error) {
    console.error('Error fetching projects:', error);
    return NextResponse.json({ error: 'Failed to fetch projects' }, { status: 500 });
  }
}