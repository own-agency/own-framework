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
