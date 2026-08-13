// Initialiseert elke acf/feitjes-slider-instance (blocks/feitjes-slider/
// render.php). De hele Swiper-opzet zit in packages/nav-slider.js, gedeeld
// met acf/content-blokken-slider.
//
// Draait WEL ook in de editor (src/js/editor-scripts.js), anders dan
// content-slider.js: de feitjes van dit block zijn gewone attributes, geen
// InnerBlocks, dus render.php kan in de editor exact dezelfde slider-markup
// teruggeven als op de front-end — er is hier niets bewerkbaars dat een
// Swiper zou kunnen verbergen.
import { initOnAppearance } from "../packages/init-on-appearance.js";
import { initNavSlider } from "../packages/nav-slider.js";

initOnAppearance("[data-own-facts-slider]", (root) => {
  // Alleen de lg-stap is instelbaar ("Aantal naast elkaar", editor.js).
  // Een feitje is maar een getal met een kort label, dus er passen er
  // gerust meer naast elkaar dan bij de content-blokken-slider — vandaar
  // 1.3 (met een duidelijke rand van de volgende) op mobiel en 2 vanaf sm,
  // i.p.v. pas vanaf md.
  const perView = Number(root.dataset.ownFactsSlider) || 4;

  initNavSlider(root, {
    base: "own-facts-slider",
    slidesPerView: 1.3,
    breakpoints: {
      640: { slidesPerView: 2 },
      1024: { slidesPerView: perView },
    },
  });
});
