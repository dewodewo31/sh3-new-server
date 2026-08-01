/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    domains: ["127.0.0.1", "localhost"],
    dangerouslyAllowLocalIP: true,
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/storage/**',
      },
      {
        protocol: 'http',
        hostname: '127.0.0.1',
        port: '8000',
        pathname: '/storage/**',
      },
      // ← tambah ini sesuai domain backend production
      {
        protocol: 'https',
        hostname: 'server-sh3.cloud', // ← ganti dengan domain backend
        pathname: '/storage/**',
      },
    ],
  },
};

export default nextConfig;