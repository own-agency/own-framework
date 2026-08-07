// live-search.js registers Alpine stores read by search-modal.php's markup —
// must resolve before ./packages/alpine.js's own Alpine.start() call runs
// (same file's own comment: "Components must be registered before start()"),
// so this import stays first regardless of where the other script imports
// below end up.
import "./scripts/live-search.js";
import "./packages/alpine.js";
import "./scripts/header.js";
import "./scripts/header-nav-touch.js";
import "./scripts/image-slider.js";
import "./scripts/hero.js";
