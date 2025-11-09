import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Allow configuring HMR host for LAN access (e.g., 192.168.x.x)
const hmrHost = process.env.VITE_HMR_HOST || 'localhost';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
  server: {
    host: true, // listen on 0.0.0.0 inside container
    port: 5173,
    strictPort: true,
    hmr: {
      host: hmrHost,
      port: 5173,
    },
  },
});
