// @ts-nocheck
import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = fileURLToPath(new URL(".", import.meta.url));
const isProduction = process.env.NODE_ENV === "production";

export default defineConfig({
  staged: {
    "*": "vp check --fix",
  },
  lint: { options: { typeAware: true, typeCheck: true } },
  plugins: [tailwindcss()],
  root: path.resolve(__dirname),
  base: "./",
  server: {
    watch: {
      usePolling: true,
    },
  },
  build: {
    outDir: path.resolve(__dirname, "assets"),
    emptyOutDir: true,
    manifest: true,
    // Tailwind v4's Lightning CSS engine already emits CSS that requires
    // Safari 16.4+ / Chrome 111+ / Firefox 128+ (e.g. native nesting,
    // @property) — there's no browserslist knob for that, it's fixed by the
    // engine. Matching the JS target to the same baseline avoids esbuild
    // downleveling syntax for browsers that couldn't render the CSS anyway.
    target: ["chrome111", "firefox128", "safari16.4", "edge111"],
    rolldownOptions: {
      // Fonts live at a fixed path (this theme's fonts/ dir) and are served
      // as-is by WordPress, not bundled — mark them external so Vite doesn't
      // warn about being unable to resolve them at build time.
      external: [/^\/wp-content\/themes\/own-framework\/fonts\//],
      input: {
        main: path.resolve(__dirname, "src/js/main.js"),
        style: path.resolve(__dirname, "src/css/app.css"),
        editorStyle: path.resolve(__dirname, "src/css/editor-only.css"),
      },
      output: {
        entryFileNames: "js/bundle.[hash].js",
        assetFileNames: ({ name }) => {
          if (/\.css$/.test(name ?? "")) {
            return "css/app.[hash][extname]";
          }
          if (/\.(woff|woff2|eot|ttf|otf)$/.test(name ?? "")) {
            return "fonts/[name].[hash][extname]";
          }
          if (/\.(png|jpe?g|gif|svg|webp|avif)$/.test(name ?? "")) {
            return "images/[name].[hash][extname]";
          }
          return "assets/[name].[hash][extname]";
        },
      },
    },
  },
  esbuild: {
    pure: isProduction ? ["console.log", "console.error"] : [],
  },
  optimizeDeps: {
    include: ["alpinejs"],
  },
});
