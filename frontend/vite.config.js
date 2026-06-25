import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5000,
    strictPort: true,
    hmr: {
      clientPort: 8080
    },
    watch: {
      usePolling: false
    },
    proxy: {
      '/check-domain': { target: 'http://localhost:8000', changeOrigin: true },
      '/login': { target: 'http://localhost:8000', changeOrigin: true },
      '/register': { target: 'http://localhost:8000', changeOrigin: true },
      '/logout': { target: 'http://localhost:8000', changeOrigin: true },
      '/users': { target: 'http://localhost:8000', changeOrigin: true }
    }
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true
  }
});