// Initialiseert acf/hero's achtergrond-slideshow (blocks/hero/render.php,
// variant "slider") en respecteert prefers-reduced-motion voor zowel de
// slideshow als de achtergrondvideo — geen los "pauzeer animaties"-knopje,
// de voorkeur van de bezoeker's OS/browser is hier voldoende.
import Swiper from "swiper";
import { Autoplay, EffectFade, Pagination } from "swiper/modules";

const prefersReducedMotion = window.matchMedia(
  "(prefers-reduced-motion: reduce)",
).matches;

function initHeroSlider(root) {
  // data-own-hero-slider draagt de gekozen effect-waarde zelf (render.php) —
  // geen los data-attribute nodig voor iets dat toch al een bestaand
  // attribute's waarde kan zijn.
  const effect = root.dataset.ownHeroSlider === "fade" ? "fade" : "slide";
  const slideCount = root.querySelectorAll(".swiper-slide").length;
  // .own-hero-pagination bestaat alleen als "Paginatie-bolletjes tonen"
  // aanstaat (render.php) — de module wordt altijd meegeladen, maar de
  // "pagination"-optie hieronder alleen doorgegeven als het element er ook
  // echt is.
  const paginationEl = root.querySelector(".own-hero-pagination");

  // Reduced-motion schakelt alleen het vanzelf-doorschuiven uit (Autoplay),
  // niet de paginatie zelf: een bezoeker die zelf op een bolletje klikt
  // vraagt daar expliciet om, dat is geen "automatische" beweging.
  new Swiper(root, {
    modules: [
      Pagination,
      ...(effect === "fade" ? [EffectFade] : []),
      ...(prefersReducedMotion ? [] : [Autoplay]),
    ],
    effect,
    ...(effect === "fade" ? { fadeEffect: { crossFade: true } } : {}),
    loop: slideCount > 1,
    allowTouchMove: false,
    ...(prefersReducedMotion ? {} : { autoplay: { delay: 5000, disableOnInteraction: false } }),
    ...(paginationEl && { pagination: { el: paginationEl, clickable: true } }),
  });
}

function initHeroVideo(video) {
  if (!prefersReducedMotion) return;
  // De video blijft als (poster/eerste-frame) achtergrond staan, alleen
  // zonder beweging — autoplay-attribute weghalen voorkomt dat browsers
  // 'm alsnog automatisch herstarten (bv. na een tabwissel).
  video.pause();
  video.removeAttribute("autoplay");
}

document.querySelectorAll("[data-own-hero-slider]").forEach(initHeroSlider);
document.querySelectorAll("[data-own-hero-video]").forEach(initHeroVideo);
