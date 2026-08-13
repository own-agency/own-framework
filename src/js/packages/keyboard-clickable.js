// De vorige/volgende-knoppen van de sliders zijn <div role="button">, geen
// echte <button>: die kregen native browser-chrome dat niet met Tailwind-
// klassen te overrulen was. Een <div> krijgt Enter/Spatie-bediening echter
// niet gratis zoals een echte <button> — deze helper voegt precies dat ene
// stukje toetsenbordgedrag toe dat daardoor ontbreekt.
//
// Gedeeld door scripts/image-slider.js en scripts/content-slider.js, die
// allebei exact dezelfde knoppen renderen (acf/afbeeldingen-slider resp.
// acf/content-blokken-slider) — hierheen verplaatst zodra dat tweede block
// dezelfde logica nodig had, niet vooruitlopend daarop.
export function makeKeyboardClickable(el) {
  el?.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      el.click();
    }
  });
}
