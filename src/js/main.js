// live-search.js, notifications.js, vacatures-filter.js and agenda-filter.js
// register Alpine stores/data components read by search-modal.php's,
// notifications/includes/render.php's, blocks/vacatures/render.php's and
// blocks/agenda/render.php's own markup — must resolve before
// ./packages/alpine.js's own Alpine.start() call runs (same file's own
// comment: "Components must be registered before start()"), so these four
// imports stay above it regardless of where the other script imports below
// end up.
import "./scripts/live-search.js";
import "./scripts/notifications.js";
import "./scripts/vacatures-filter.js";
import "./scripts/agenda-filter.js";
import "./packages/alpine.js";
import "./scripts/header.js";
import "./scripts/header-nav-touch.js";
import "./scripts/image-slider.js";
import "./scripts/content-slider.js";
import "./scripts/facts-slider.js";
import "./scripts/vacatures-uitgelicht-slider.js";
import "./scripts/agenda-uitgelicht-slider.js";
import "./scripts/hero.js";
import "./scripts/google-map.js";
import "./scripts/video.js";
