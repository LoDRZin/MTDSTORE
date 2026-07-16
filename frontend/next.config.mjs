import { withSentryConfig } from "@sentry/nextjs";

/** @type {import('next').NextConfig} */
const nextConfig = {
  // Optimize images from external sources if needed
  images: {
    remotePatterns: [],
  },
};

export default withSentryConfig(nextConfig, {
  // Suppress source map upload warnings in development
  silent: true,
  // Disable Sentry telemetry
  telemetry: false,
});
