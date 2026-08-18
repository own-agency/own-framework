// Initialiseert elke acf/agenda-uitgelicht-instance die als "Slider" staat
// ingesteld (blocks/agenda-uitgelicht/render.php). Zelfde Swiper-opzet als
// acf/vacatures-uitgelicht (packages/nav-slider.js) — draait ook in de editor
// (src/js/editor-scripts.js): de kaarten komen uit een server-side WP_Query
// (geen InnerBlocks), dus render.php geeft in de editor exact dezelfde
// slider-markup terug als op de front-end.
import { initOnAppearance } from "../packages/init-on-appearance.js";
import { initNavSlider } from "../packages/nav-slider.js";

initOnAppearance("[data-own-agenda-uitgelicht-slider]", (root) => {
  initNavSlider(root, {
    base: "own-agenda-uitgelicht",
    slidesPerView: 1.1,
    breakpoints: {
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
    },
  });
});
