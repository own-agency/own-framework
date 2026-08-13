// live-search.js and notifications.js register Alpine stores/data components
// read by search-modal.php's and notifications/includes/render.php's own
// markup — must resolve before ./packages/alpine.js's own Alpine.start() call
// runs (same file's own comment: "Components must be registered before
// start()"), so these two imports stay above it regardless of where the
// other script imports below end up.
import "./scripts/live-search.js";
import "./scripts/notifications.js";
import "./packages/alpine.js";
import "./scripts/header.js";
import "./scripts/header-nav-touch.js";
import "./scripts/image-slider.js";
import "./scripts/content-slider.js";
import "./scripts/facts-slider.js";
import "./scripts/hero.js";
import "./scripts/google-map.js";
