import Plyr from "plyr";

// Plyr berekent voor een native HTML5-video ALLEEN een kloppende
// beeldverhouding (en dus een wrapper met echte hoogte) als je zelf een
// "ratio" meegeeft — Plyr's eigen html5.setup() slaat setAspectRatio() over
// wanneer config.ratio leeg is, ook al kan diezelfde
// functie de echte video-afmetingen (videoWidth/videoHeight) er wél
// automatisch uit halen zodra ze bekend zijn. Zonder "ratio" blijft
// ".plyr__video-wrapper" dus zonder eigen hoogte (percentage-hoogte tegen een
// ouder (".plyr") die zelf ook geen hoogte heeft), en toonden zowel de
// poster als de video zwarte balken — geen fit-probleem (cover/contain),
// maar een ontbrekende wrapper-hoogte. Vandaar: de echte beeldverhouding
// zelf uitlezen (videoWidth/videoHeight) zodra bekend, en pas dán Plyr
// initialiseren — i.p.v. een vaste "16:9" voor elke video, die voor een
// toekomstige verticale/vierkante upload weer hetzelfde probleem in
// omgekeerde vorm zou geven (uitrekken i.p.v. balken).
function initPlyr(video) {
  const ratio =
    video.videoWidth && video.videoHeight
      ? `${video.videoWidth}:${video.videoHeight}`
      : "16:9";

  new Plyr(video, {
    ratio,
    i18n: {
      restart: "Opnieuw starten",
      rewind: "{seektime}s terugspoelen",
      play: "Afspelen",
      pause: "Pauzeren",
      fastForward: "{seektime}s vooruitspoelen",
      seek: "Zoeken",
      seekLabel: "{currentTime} van {duration}",
      played: "Afgespeeld",
      buffered: "Gebufferd",
      currentTime: "Huidige tijd",
      duration: "Totale tijd",
      volume: "Volume",
      mute: "Dempen",
      unmute: "Dempen opheffen",
      enterFullscreen: "Volledig scherm",
      exitFullscreen: "Volledig scherm sluiten",
      settings: "Instellingen",
      speed: "Snelheid",
      normal: "Normaal",
      quality: "Kwaliteit",
      loop: "Herhalen",
    },
  });
}

// Alleen core/video (".wp-block-video"), nooit blocks/hero's eigen
// achtergrondvideo (data-own-hero-video) — die heeft bewust geen bediening.
// Alleen video's MET het "controls"-attribuut: een editor die "Toon
// afspeelknoppen" uitzet, wil een kale autoplay/loop-video, geen Plyr-UI
// eroverheen.
document.querySelectorAll(".wp-block-video video[controls]").forEach((video) => {
  // "preload" staat standaard op "metadata" (core/video's eigen attribuut-
  // default), maar die fetch is async — readyState is bij het draaien van dit
  // (deferred) script vaak nog HAVE_NOTHING. Pas initialiseren zodra de
  // afmetingen echt bekend zijn voorkomt zowel de gok op "16:9" als een
  // zichtbare her-layout achteraf.
  if (video.readyState >= 1) {
    initPlyr(video);
  } else {
    video.addEventListener("loadedmetadata", () => initPlyr(video), {
      once: true,
    });
  }
});
