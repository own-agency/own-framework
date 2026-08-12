// Initialiseert elke acf/afbeeldingen-slider-instance (blocks/afbeeldingen-
// slider/render.php), beide "sliderVersion"-varianten: "default" (paginatie-
// bolletjes, optioneel) en "thumbs" (miniaturenstrook i.p.v. bolletjes).
// Prev/next-knoppen worden via .closest(".own-image-slider") + querySelector
// gescoped op de eigen sectie i.p.v. een uniek id/klasse per instance —
// blijft correct met willekeurig veel sliders op één pagina zonder
// id-botsingen.
import Swiper from "swiper";
import { Navigation, Pagination, Thumbs } from "swiper/modules";
import { initOnAppearance } from "../packages/init-on-appearance.js";

// prevEl/nextEl zijn <div role="button">, geen echte <button> (die kregen
// native browser-chrome die niet met Tailwind-klassen te overrulen was) —
// zonder deze listener werken Enter/Spatie dan niet, wat een echt <button>
// gratis zou krijgen.
function makeKeyboardClickable(el) {
  el?.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      el.click();
    }
  });
}

function initSlider(root) {
  const section = root.closest(".own-image-slider");
  const prevEl = section?.querySelector(".own-image-slider-prev") ?? null;
  const nextEl = section?.querySelector(".own-image-slider-next") ?? null;
  makeKeyboardClickable(prevEl);
  makeKeyboardClickable(nextEl);

  // "thumbs" (render.php): de miniaturenstrook moet als Swiper-instance al
  // bestaan vóórdat de hoofdslider 'm via "thumbs.swiper" kan koppelen —
  // Swiper's Thumbs-module linkt twee instances, 't maakt er zelf geen aan,
  // en synchroniseert daarna zelf in beide richtingen (thumb klikken schuift
  // de hoofdslider door, de hoofdslider doorschuiven markeert de juiste
  // thumb) — geen losse "slideChangeTransitionStart"-listener nodig, dat zat
  // alleen in oudere Swiper-versies. "loop" op allebei (i.p.v. op alleen de
  // hoofdslider): anders raakt de index-telling van de strook uit sync zodra
  // de hoofdslider van laatste naar eerste slide wrapt.
  //
  // slideCount > 1 (zelfde bewaking als hero.js's eigen loop-optie): "loop"
  // met te weinig slides — vooral in combinatie met "direction: vertical" —
  // kan Swiper's eigen transform-berekening laten ontsporen naar een absurd
  // groot getal (de browser's eigen max. layoutgrootte, 2^24px, is dan
  // zichtbaar als een absurde afbeeldingshoogte). "centeredSlides" zat om
  // dezelfde reden niet in deze versie: dat verergerde precies dat probleem
  // zodra het aantal afbeeldingen klein is t.o.v. slidesPerView.
  if (root.dataset.ownSlider === "thumbs") {
    const slideCount = root.querySelectorAll(".swiper-slide").length;
    const loop = slideCount > 1;
    const thumbsEl = section?.querySelector(".own-image-slider-thumbs") ?? null;
    const thumbsSwiper = thumbsEl
      ? new Swiper(thumbsEl, {
          direction: "vertical",
          loop,
          slidesPerView: 4,
          spaceBetween: 10,
          watchOverflow: true,
          watchSlidesProgress: true,
        })
      : null;

    new Swiper(root, {
      modules: [Navigation, ...(thumbsSwiper ? [Thumbs] : [])],
      loop,
      spaceBetween: 10,
      watchOverflow: true,
      preventInteractionOnTransition: true,
      navigation: { nextEl, prevEl },
      ...(thumbsSwiper && { thumbs: { swiper: thumbsSwiper } }),
    });
    return;
  }

  // .own-image-slider-pagination bestaat alleen als "Paginatie-bolletjes
  // tonen" aanstaat (render.php) — de module wordt altijd meegeladen, maar
  // de "pagination"-optie hieronder alleen doorgegeven als het element er
  // ook echt is.
  const paginationEl = root.querySelector(".own-image-slider-pagination");

  new Swiper(root, {
    modules: [Navigation, Pagination],
    slidesPerView: 1.2,
    spaceBetween: 20,
    breakpoints: {
      1024: { slidesPerView: 2 },
    },
    navigation: { nextEl, prevEl },
    ...(paginationEl && { pagination: { el: paginationEl, clickable: true } }),
  });
}

initOnAppearance("[data-own-slider]", initSlider);
