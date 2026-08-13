// Initialiseert elk acf/google-maps-block (blocks/google-maps/render.php).
//
// Draait ALLEEN op de front-end (staat niet in src/js/editor-scripts.js):
// render.php geeft in de editor met opzet geen kaart-element terug maar een
// placeholder, zodat het bewerken van een pagina geen Maps-verzoeken doet.
// Elke kaartlading is een betaalde API-call, en in de editor rendert een block
// bij élke attribuutwijziging opnieuw.
//
// Maps JAVASCRIPT API, niet de Embed API: alleen de JS-API kan de
// straatweergave-poppetje en de satelliet-knop uitzetten (zie render.php's
// eigen docblock voor waarom de Embed API dat niet kan). Bewust ZONDER
// "libraries=places": het zoekvak/de autocomplete is alleen in de admin nodig
// (het adresveld van SCF), niet hier.
const MAPS_CALLBACK = "ownGoogleMapsReady";
let mapsApiPromise = null;

// Eén <script> voor de hele pagina, ook met meerdere kaarten erop: Google's
// eigen loader klaagt (en laadt dubbel) als hetzelfde script twee keer wordt
// toegevoegd.
function loadMapsApi(apiKey) {
  if (mapsApiPromise) return mapsApiPromise;

  mapsApiPromise = new Promise((resolve, reject) => {
    window[MAPS_CALLBACK] = () => resolve(window.google.maps);

    const script = document.createElement("script");
    // "loading=async" is Google's eigen aanbevolen vorm en voorkomt hun
    // console-waarschuwing over synchroon laden; de callback hierboven vertelt
    // ons wanneer de API klaar is.
    script.src =
      "https://maps.googleapis.com/maps/api/js?key=" +
      encodeURIComponent(apiKey) +
      "&loading=async&callback=" +
      MAPS_CALLBACK;
    script.async = true;
    script.onerror = () => reject(new Error("Google Maps kon niet laden"));
    document.head.appendChild(script);
  });

  return mapsApiPromise;
}

function initMap(maps, el) {
  const lat = parseFloat(el.dataset.mapLat);
  const lng = parseFloat(el.dataset.mapLng);
  if (Number.isNaN(lat) || Number.isNaN(lng)) return;

  const position = { lat, lng };

  const map = new maps.Map(el, {
    center: position,
    zoom: parseInt(el.dataset.mapZoom, 10) || 15,
    // Waar dit block om gevraagd is: straatweergave, satelliet/terrein en
    // volledig scherm staan uit. Dit is een "waar zitten we"-kaart, geen
    // verkenningskaart. Zoomen blijft wel mogelijk, dat is het enige dat een
    // bezoeker hier echt nodig heeft.
    streetViewControl: false,
    mapTypeControl: false,
    fullscreenControl: false,
    zoomControl: true,
    // "cameraControl" is een LOS knopje (roteren/kantelen/pannen gebundeld
    // achter één icoon) dat los staat van fullscreenControl/mapTypeControl/
    // streetViewControl hierboven — Google's eigen documentatie noemt het
    // niet standaard uit, en zonder deze regel stond het pal boven de zoom-
    // knoppen: precies "de zoom/control-knoppen zien er stuk uit" zonder dat
    // er iets kapot was, gewoon een control die nooit gevraagd is. rotateControl/
    // panControl hoeven niet apart uit: die zaten al verborgen ALS onderdeel
    // van dit knopje, niet als eigen, zichtbare controls (empirisch nagekeken,
    // niet aangenomen — zie git-historie van dit bestand).
    cameraControl: false,
    // "cooperative" laat een scroll over de kaart de PAGINA scrollen; alleen
    // ctrl/⌘+scroll (of twee vingers) zoomt. Zonder dit blijft de pagina op
    // mobiel haken zodra je de kaart raakt tijdens het scrollen.
    gestureHandling: "cooperative",
  });

  // Google's eigen standaardspeld, met opzet geen custom icon — zie
  // render.php's docblock voor waarom marker-styling hier (nog) niet zit.
  new maps.Marker({
    position,
    map,
    title: el.dataset.mapTitle || undefined,
  });

  // De kaart hercentreren na een resize: Google houdt bij een breedtewijziging
  // de linkerbovenhoek vast, waardoor het middelpunt (en dus de marker) uit
  // beeld schuift — bijvoorbeeld bij het draaien van een telefoon. Eigen kleine
  // timer i.p.v. een gedeelde debounce-helper: dit is de enige plek in het
  // theme die er een nodig heeft (zie CLAUDE.md's plaatsingsregel).
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => map.setCenter(position), 200);
  });
}

const elements = document.querySelectorAll("[data-own-google-map]");
if (elements.length) {
  const apiKey = elements[0].dataset.mapKey;
  if (apiKey) {
    loadMapsApi(apiKey)
      .then((maps) => elements.forEach((el) => initMap(maps, el)))
      // Een kapotte sleutel/geblokkeerde API mag nooit de rest van de pagina
      // meeslepen; Google logt zelf al een duidelijke melding in de console.
      .catch(() => {});
  }
}
