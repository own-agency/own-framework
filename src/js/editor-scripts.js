// Losse, minimale entry voor de block-editor (enqueue-scripts-styles.php's
// ownframework_enqueue_editor_assets(), niet main.js). Alleen de Swiper-
// initialisatie die nodig is om acf/hero en acf/afbeeldingen-slider's eigen
// paginatie-bolletjes/miniaturenstrook ook in de editor te tonen — bewust
// GEEN Alpine.start(), header.js, header-nav-touch.js of live-search.js: die
// gaan uit van de volledige pagina (een echte <header>, de zoekmodal se eigen
// ancestor-context, enz.) die er in een block-editor-iframe met alleen dít
// blok's eigen preview-DOM niet is. Alpine die daar toch overheen scant kan
// vastlopen op een ontbrekend element/store, en een editor die daardoor
// helemaal niet meer klikbaar is, is een veel groter probleem dan
// onopgemaakte paginatie-bolletjes.
import "./scripts/hero.js";
import "./scripts/image-slider.js";
// acf/feitjes-slider heeft geen InnerBlocks, dus render.php geeft in de editor
// exact dezelfde slider-markup terug als op de front-end — die mag hier dus
// gewoon echt draaien. acf/content-blokken-slider ontbreekt hier met opzet:
// dat block toont in de editor een bewerkbaar grid i.p.v. een slider (zie
// blocks/content-blokken-slider/render.php). acf/logo-slider staat hier NIET
// (meer): sinds dat block een pure CSS-marquee is (geen Swiper), draait er
// nergens JS voor — front-end en editor tonen vanzelf dezelfde animatie.
import "./scripts/facts-slider.js";
// acf/vacatures-uitgelicht (Slider-weergave) — zelfde reden als acf/feitjes-
// slider hierboven: de kaarten komen uit een server-side WP_Query, geen
// InnerBlocks, dus render.php geeft in de editor exact dezelfde slider-markup
// terug als op de front-end.
import "./scripts/vacatures-uitgelicht-slider.js";
// acf/agenda-uitgelicht (Slider-weergave) — zelfde reden/opzet als acf/
// vacatures-uitgelicht hierboven.
import "./scripts/agenda-uitgelicht-slider.js";
// acf/news-uitgelicht (Slider-weergave) — zelfde reden/opzet als acf/
// vacatures-uitgelicht/acf/agenda-uitgelicht hierboven.
import "./scripts/news-uitgelicht-slider.js";
// acf/verhalen-uitgelicht (Slider-weergave) — zelfde reden/opzet als acf/
// vacatures-uitgelicht/acf/agenda-uitgelicht/acf/news-uitgelicht hierboven.
import "./scripts/verhalen-uitgelicht-slider.js";
