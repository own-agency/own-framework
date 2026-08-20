// Initialiseert elke acf/verhalen-uitgelicht-instance die als "Slider" staat
// ingesteld (blocks/verhalen-uitgelicht/render.php). Zelfde Swiper-opzet als
// acf/vacatures-uitgelicht/acf/agenda-uitgelicht/acf/news-uitgelicht
// (packages/nav-slider.js) — draait ook in de editor (src/js/
// editor-scripts.js): de kaarten komen uit een server-side WP_Query (geen
// InnerBlocks), dus render.php geeft in de editor exact dezelfde
// slider-markup terug als op de front-end.
import { initOnAppearance } from "../packages/init-on-appearance.js";
import { initNavSlider } from "../packages/nav-slider.js";

initOnAppearance("[data-own-verhalen-uitgelicht-slider]", (root) => {
  initNavSlider(root, {
    base: "own-verhalen-uitgelicht",
    slidesPerView: 1.1,
    breakpoints: {
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
    },
  });
});
