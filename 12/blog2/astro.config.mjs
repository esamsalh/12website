import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

export default defineConfig({
  site: 'https://toolrar.com',
  base: '/',
  output: 'static',
  integrations: [sitemap({
    filter: (page) => !page.includes('/admin')
  })],
  build: {
    format: 'file'
  },
  markdown: {
    shikiConfig: {
      theme: 'dracula'
    }
  },
  server: {
    host: true
  }
});
