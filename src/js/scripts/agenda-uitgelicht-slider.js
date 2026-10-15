// Initialiseert elke acf/agenda-uitgelicht-instance die als "Slider" staat
// ingesteld (blocks/agenda-uitgelicht/render.php). Zelfde Swiper-opzet als
// acf/vacatures-uitgelicht (packages/nav-slider.js) — draait ook in de editor
// (src/js/editor-scripts.js): de kaarten komen uit een server-side WP_Query
// (geen InnerBlocks), dus render.php geeft in de editor exact dezelfde
// slider-markup terug als op de front-end.
import { initOnAppearance } from "../packages/init-on-appearance.js";
import { initNavSlider } from "../packages/nav-slider.js";

//
// "Aantal naast elkaar" (data-attribuut, 1–3) is het maximum. De breakpoints
// meten de breedte van de slider zelf (breakpointsBase: "container") i.p.v.
// de viewport — 576/896px = Tailwinds @xl/@4xl die het raster in render.php
// gebruikt — zodat de slider in een smalle kolom niet 3 kaarten in 300px
// propt.
initOnAppearance("[data-own-agenda-uitgelicht-slider]", (root) => {
  const columns = Number(root.dataset.ownAgendaUitgelichtSlider) || 3;

  initNavSlider(root, {
    base: "own-agenda-uitgelicht",
    slidesPerView: 1.1,
    breakpointsBase: "container",
    breakpoints: {
      576: { slidesPerView: Math.min(2, columns) },
      896: { slidesPerView: columns },
    },
  });
});
