import { MetadataRoute } from 'next';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000';

  // Fetch all products to include in sitemap
  let products: { slug: string; updated_at?: string }[] = [];
  try {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || "/api/v1";
    const res = await fetch(`${apiUrl}/products`, { next: { revalidate: 3600 } });
    if (res.ok) {
      const data = await res.json();
      products = data.data || [];
    }
  } catch (err) {
    console.error("Error fetching products for sitemap", err);
  }

  const productUrls = products.map((product) => ({
    url: `${baseUrl}/produtos/${product.slug}`,
    lastModified: new Date(product.updated_at || new Date()),
    changeFrequency: 'weekly' as const,
    priority: 0.8,
  }));

  return [
    {
      url: `${baseUrl}`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/consultar`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    ...productUrls,
  ];
}
