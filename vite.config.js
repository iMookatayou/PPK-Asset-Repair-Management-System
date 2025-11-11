import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const hmrHost = env.VITE_HMR_HOST || 'localhost';
  const hmrPort = Number(env.VITE_HMR_PORT || 5173);
  const hmrProtocol = env.VITE_HMR_PROTOCOL || 'http';
  const useHttps = hmrProtocol === 'https';

  return {
    plugins: [
      laravel({
        input: ['resources/css/app.css', 'resources/js/app.js'],
        refresh: true,
      }),
    ],
    server: {
      host: true,
      port: 5173,
      strictPort: true,
      https: useHttps,
      hmr: {
        host: hmrHost,
        port: hmrPort,
        protocol: hmrProtocol,
      },
    },
  };
});
