// Initialiseert elke acf/afbeeldingen-slider-instance (blocks/afbeeldingen-
// slider/render.php). Prev/next-knoppen worden via .closest(".own-image-
// slider") + querySelector gescoped op de eigen sectie i.p.v. een uniek id/
// klasse per instance — blijft correct met willekeurig veel sliders op één
// pagina zonder id-botsingen.
import Swiper from "swiper";
import { Navigation, Pagination } from "swiper/modules";

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

document.querySelectorAll("[data-own-slider]").forEach(initSlider);
