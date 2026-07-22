import { withSentryConfig } from "@sentry/nextjs";

/** @type {import('next').NextConfig} */
const nextConfig = {
  // Optimize images from external sources if needed
  images: {
    formats: ['image/avif', 'image/webp'],
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'lh3.googleusercontent.com',
        pathname: '/**',
      },
      {
        protocol: 'http',
        hostname: 'mtdstore.test',
        pathname: '/**',
      },
      {
        protocol: 'https',
        hostname: 'mtdstore.test',
        pathname: '/**',
      },
      {
        protocol: 'https',
        hostname: 'kbkuipmqoqlackykxqsc.supabase.co',
        pathname: '/**',
      },
    ],
  },
};

export default withSentryConfig(nextConfig, {
  // Suppress source map upload warnings in development
  silent: true,
  // Disable Sentry telemetry
  telemetry: false,
});
