// Initialiseert elke acf/content-blokken-slider-instance (blocks/content-
// blokken-slider/render.php). De hele Swiper-opzet (knoppen scopen op de
// eigen sectie, toetsenbordbediening, paginatie) zit in packages/nav-slider.js,
// gedeeld met acf/feitjes-slider — hier staat alleen nog wat dít block
// eigen is.
//
// Draait bewust ALLEEN op de front-end: dit script staat niet in
// src/js/editor-scripts.js, omdat render.php in de editor met opzet geen
// Swiper-markup rendert (zie dat bestand's eigen docblock over waarom de
// editor een grid toont i.p.v. een slider).
import { initOnAppearance } from "../packages/init-on-appearance.js";
import { initNavSlider } from "../packages/nav-slider.js";

initOnAppearance("[data-own-content-slider]", (root) => {
  // Alleen de lg-stap is instelbaar ("Aantal naast elkaar", editor.js).
  // Daaronder ligt het aantal vast: een content-kaart met kop, tekst én
  // knoppen wordt bij 3-4 kolommen op een telefoon/tablet onleesbaar smal,
  // ongeacht wat er gekozen is. De 1.1 op mobiel laat bewust een rand van de
  // volgende kaart zien — dat is het signaal dát er meer naast staat.
  const perView = Number(root.dataset.ownContentSlider) || 3;

  initNavSlider(root, {
    base: "own-content-slider",
    slidesPerView: 1.1,
    breakpoints: {
      768: { slidesPerView: 2 },
      1024: { slidesPerView: perView },
    },
  });
});
