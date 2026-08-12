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
