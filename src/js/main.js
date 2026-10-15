// live-search.js, notifications.js, vacatures-filter.js, agenda-filter.js,
// news-filter.js and verhalen-filter.js register Alpine stores/data
// components read by search-modal.php's, notifications/includes/
// render.php's, blocks/vacatures/render.php's, blocks/agenda/render.php's,
// blocks/news/render.php's and blocks/verhalen/render.php's own markup —
// must resolve before ./packages/alpine.js's own Alpine.start() call runs
// (same file's own comment: "Components must be registered before
// start()"), so these imports stay above it regardless of where the other
// script imports below end up.
import "./scripts/live-search.js";
import "./scripts/notifications.js";
import "./scripts/vacatures-filter.js";
import "./scripts/agenda-filter.js";
import "./scripts/news-filter.js";
import "./scripts/verhalen-filter.js";
import "./scripts/footer-topbar.js";
import "./scripts/melding.js";
import "./packages/alpine.js";
import "./scripts/header.js";
import "./scripts/header-nav-touch.js";
import "./scripts/gtranslate-dropdown.js";
import "./scripts/image-slider.js";
import "./scripts/content-slider.js";
import "./scripts/facts-slider.js";
import "./scripts/vacatures-uitgelicht-slider.js";
import "./scripts/agenda-uitgelicht-slider.js";
import "./scripts/news-uitgelicht-slider.js";
import "./scripts/verhalen-uitgelicht-slider.js";
import "./scripts/hero.js";
import "./scripts/google-map.js";
import "./scripts/video.js";
