import Alpine from "alpinejs";

// Alpine.data(), niet Alpine.store() zoals live-search.js's searchDialog:
// dismiss-status is puur LOKAAL aan elke kaart (notifications/includes/
// render.php geeft elke kaart al zijn eigen sessionStorage-key mee via het
// id-argument hier), niets hoeft van buiten deze kaart bereikbaar te zijn.
// Ook geen los x-data="{...}" object-literal per kaart (own-mobile-menu's
// mobileMenuOpen-patroon, blocks/header/render.php) — render.php doet dit
// voor élke rij in de "Meldingen" repeater, en dezelfde init/dismiss-logica
// per kaart in de markup zelf herhalen zou dezelfde paar regels N keer
// dupliceren. Alpine.data() is precies hiervoor: herbruikbare component-
// logica met eigen lokale state, ingeregistreerd voordat ./packages/
// alpine.js's eigen Alpine.start() draait (zelfde vereiste als live-
// search.js's eigen store-registratie).
//
// sessionStorage, geen localStorage of cookie: een weggeklikte melding mag
// pas terugkomen bij een VOLGENDE bezoek (browser echt opnieuw geopend) —
// sessionStorage wist zichzelf zodra de browsersessie eindigt, dus dat gedrag
// komt gratis mee, zonder een vervaldatum of server-side status bij te
// houden.
Alpine.data("ownNotificationCard", (id) => ({
  visible: true,

  init() {
    this.visible = sessionStorage.getItem(this._storageKey()) !== "1";
  },

  dismiss() {
    this.visible = false;
    sessionStorage.setItem(this._storageKey(), "1");
  },

  _storageKey() {
    return `own-notification-dismissed-${id}`;
  },
}));

// Docks a bottom-anchored notification stack above the footer instead of
// letting it sit on top of the footer's own content once a visitor scrolls
// that far — see render.php's own "own-notifications--dock-bottom" comment
// for why this is needed at all. Same ResizeObserver-free, plain
// scroll/resize-driven CSS-custom-property approach as header.js's own
// --site-header-height: the overlap amount depends on how far into the
// footer the visitor has actually scrolled, not a fixed value, so it's
// recomputed live rather than baked into one CSS rule. ".own-footer-bottom"
// (the copyright bar), not the taller ".own-footer" above it: that section
// stacks into several screens' worth of height on mobile once its columns
// stack (own-footer's own 3-column InnerBlocks area), so clearing ITS top
// edge would push the stack far above the viewport for most of the scroll
// through it — clearing just the copyright bar fixes the actual complaint
// (the stack sitting indefinitely on top of it at the resting scroll
// position, once a visitor reaches the real bottom of the page) without
// hiding the stack while merely scrolling past the footer above it. Falls
// back to the main footer only on the rare page missing footer-bottom.
const notificationStack = document.querySelector(
  ".own-notifications.own-notifications--dock-bottom",
);
const pageFooter =
  document.querySelector(".own-footer-bottom") ||
  document.querySelector(".own-footer");

if (notificationStack && pageFooter) {
  const BASE_GAP_PX = 16; // matches the plain "bottom-4"/1rem this class replaces

  const updateNotificationsOffset = () => {
    const overlap = window.innerHeight - pageFooter.getBoundingClientRect().top;
    const offset = overlap > 0 ? overlap + BASE_GAP_PX : BASE_GAP_PX;
    notificationStack.style.setProperty(
      "--own-notifications-bottom-offset",
      `${offset}px`,
    );
  };

  updateNotificationsOffset();
  window.addEventListener("scroll", updateNotificationsOffset, { passive: true });
  window.addEventListener("resize", updateNotificationsOffset);
}
