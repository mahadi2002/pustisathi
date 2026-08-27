import { defineConfig } from 'vite';
import { resolve } from 'node:path';

/**
 * Minimal build step — CSS/JS only, no framework. This app is hand-rolled
 * PHP with vanilla JS views (see README); Vite here just minifies and
 * cache-busts the two hand-written source files into public/assets/dist/,
 * it does not restructure or bundle anything else.
 *
 * `asset()` in app/Core/Helpers.php already appends `?v=<filemtime>` to
 * whatever path a view references, so output filenames are left plain
 * (app.css / app.js) rather than content-hashed — one less thing to keep
 * in sync between this config and the PHP views.
 */
export default defineConfig({
  root: '.',
  // `outDir` (public/assets/dist) sits inside Vite's default publicDir
  // (<root>/public). With publicDir copying left on, Vite copies public/
  // into outDir — which is itself under public/ — so the copy walks into
  // its own output as it writes, recursing into public/assets/dist/assets/
  // dist/assets/dist/... until something gives out. This app doesn't need
  // Vite to copy static files at all (PHP serves public/ directly), so
  // publicDir copying is disabled outright rather than merely relocated.
  publicDir: false,
  build: {
    outDir: 'public/assets/dist',
    emptyOutDir: true,
    assetsDir: '.',
    minify: 'esbuild',
    sourcemap: false,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'public/assets/js/app.js'),
        style: resolve(__dirname, 'public/assets/css/app.css'),
      },
      output: {
        // A CSS-only rollup input still produces a JS chunk (Rollup's css
        // plugin emits an empty module for it; the real CSS comes out
        // through assetFileNames below). With a static 'app.js' string here,
        // that empty 'style' chunk and the real 'app' chunk both target the
        // same filename — whichever Rollup names first claims 'app.js', and
        // the *other* one — which can be the real JS bundle — gets bumped to
        // 'app2.js', silently shipping an empty app.js and orphaning the
        // actual code. Naming by chunk name keeps 'app.js' reserved
        // exclusively for the real entry so this can't happen regardless of
        // internal processing order; the empty style placeholder (dropped
        // by Rollup since it has no content) never contends for the name.
        entryFileNames: (chunk) => (chunk.name === 'app' ? 'app.js' : '_[name].js'),
        assetFileNames: 'app.css',
      },
    },
  },
});
