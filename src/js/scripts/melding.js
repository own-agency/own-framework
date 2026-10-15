import Alpine from "alpinejs";

// acf/melding's sluitknop (blocks/melding/render.php). Een lege sleutel
// betekent "alleen voor deze paginaweergave"; met sleutel onthoudt
// localStorage het wegklikken. try/catch: localStorage kan gooien in een
// privévenster of bij geblokkeerde site-data.
Alpine.data("ownMelding", (storageKey) => ({
  open: true,

  init() {
    if (!storageKey) {
      return;
    }
    try {
      this.open = window.localStorage.getItem(storageKey) !== "1";
    } catch (e) {}
  },

  dismiss() {
    this.open = false;
    if (!storageKey) {
      return;
    }
    try {
      window.localStorage.setItem(storageKey, "1");
    } catch (e) {}
  },
}));
