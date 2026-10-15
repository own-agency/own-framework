// Gedeelde Swiper-opzet voor de "slider met een titelrij erboven"-blocks:
// acf/content-blokken-slider (scripts/content-slider.js) en
// acf/feitjes-slider (scripts/facts-slider.js). Alles wat die twee identiek
// deden zit hier; de basisklasse, de responsive slidesPerView en autoplay/
// loop/speed komen als opties binnen. acf/logo-slider gebruikte dit ook
// (autoplay/loop/speed, en een "Navigatie tonen"-toggle), maar is sinds zijn
// herschrijving naar een pure CSS-marquee (src/css/components/logo-slider.css)
// geen Swiper-slider meer — vandaar geen optionele "hasNav" hier meer: voor de
// twee resterende gebruikers staan de vorige/volgende-knoppen altijd vast aan.
//
// "base" is de basisklasse van het block (bv. "own-feitjes-slider"), waaruit
// de rest per conventie afgeleid wordt: .<base> is de sectie waarbinnen
// gezocht wordt, .<base>-prev/-next zijn de knoppen, .<base>-pagination de
// bolletjes. Die scoping op de eigen sectie (i.p.v. een uniek id per
// instance) houdt willekeurig veel sliders op één pagina uit elkaar, én
// voorkomt dat twee VERSCHILLENDE slider-blocks elkaars knoppen oppikken.
//
// scripts/image-slider.js hoort hier bewust nog niet bij: dat bestand doet
// naast deze "default"-variant ook de miniaturenstrook ("thumbs"), die een
// tweede, gekoppelde Swiper-instance nodig heeft en dus niet in dezelfde
// vorm past. Dat block werkt al; het omschrijven levert daar geen
// functioneel verschil op, alleen risico.
import Swiper from "swiper";
import { Navigation, Pagination, Autoplay } from "swiper/modules";
import { makeKeyboardClickable } from "./keyboard-clickable.js";

export function initNavSlider(
  root,
  {
    base,
    slidesPerView,
    spaceBetween = 20,
    breakpoints,
    breakpointsBase,
    loop,
    autoplay,
    speed,
  },
) {
  const section = root.closest(`.${base}`);
  const prevEl = section?.querySelector(`.${base}-prev`) ?? null;
  const nextEl = section?.querySelector(`.${base}-next`) ?? null;
  makeKeyboardClickable(prevEl);
  makeKeyboardClickable(nextEl);

  // .<base>-pagination bestaat alleen als "Paginatie-bolletjes tonen" aanstaat
  // (render.php) — de module wordt altijd meegeladen, maar de "pagination"-
  // optie alleen doorgegeven als het element er ook echt is.
  const paginationEl = root.querySelector(`.${base}-pagination`);

  return new Swiper(root, {
    modules: [Navigation, Pagination, ...(autoplay ? [Autoplay] : [])],
    slidesPerView,
    spaceBetween,
    breakpoints,
    ...(breakpointsBase && { breakpointsBase }),
    navigation: { nextEl, prevEl },
    ...(paginationEl && { pagination: { el: paginationEl, clickable: true } }),
    ...(loop && { loop: true }),
    ...(autoplay && { autoplay }),
    ...(speed !== undefined && { speed }),
  });
}
