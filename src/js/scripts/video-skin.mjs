// Build-hulpscript (Node), geen browsercode: main.js importeert dit niet.
// Genereert inc/functions/video-player/skin.html uit de neutral-skin van
// Video.js. Draai na elke update van @videojs/html: `pnpm video-skin`.
//
// Waarom: <video-neutral-skin> zet zijn bediening in een shadow root, en
// die is van buitenaf niet aan te passen. inc/functions/video-player.php
// rendert daarom dezelfde opbouw zelf, in de gewone DOM, met een grote
// afspeelknop in het midden erbij (de officiële route: "neutral-ui", een
// layout die je zelf schrijft). Dit script houdt die opbouw gelijk aan het
// pakket, met alleen deze wijzigingen:
// - <slot>'s vervangen: de video, de poster-<img> en de thumbnail-<img>
//   komen direct op hun plek (zonder shadow root doet <slot> niets);
// - id's (en de trigger/commandfor-verwijzingen ernaar) krijgen een
//   "{uid}"-plaatshouder: ze moeten per speler uniek zijn op de pagina;
// - witruimte tussen tags weg, zodat the_content er niets in verandert;
// - de grote afspeelknop (own-video-big-play) direct na de poster.
import { readFileSync, writeFileSync } from "node:fs";
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../../..");
const require = createRequire(path.join(root, "package.json"));
const pkgDir = path.dirname(require.resolve("@videojs/html/package.json"));
const version = JSON.parse(readFileSync(path.join(pkgDir, "package.json"), "utf8")).version;
const source = readFileSync(
  path.join(pkgDir, "dist/default/internal/skins/neutral-video/template.js"),
  "utf8",
);

const match = source.match(/createTemplate\(`([\s\S]*?)`\)/);
if (!match) {
  throw new Error("Template niet gevonden: is de opbouw van het pakket veranderd?");
}
let html = match[1].replace(/>\s+</g, "><").trim();

const replace = (from, to) => {
  if (!html.includes(from)) {
    throw new Error(`Niet gevonden in de template: ${from}`);
  }
  html = html.replace(from, to);
};

replace('<slot name="poster">', "");
replace('<img alt="" decoding="async" class="media-poster-image"></slot>', '<img alt="" decoding="async" class="media-poster-image" src="{poster}">');
replace('<slot name="thumbnail">', "");
replace('class="media-slider-thumbnail-image"></slot>', 'class="media-slider-thumbnail-image">');
replace("<slot></slot>", "{video}");

// De grote knop direct na de poster: dan ligt hij erboven, en onder de
// bediening/dialogen die later in de opbouw komen.
replace(
  "</media-poster>",
  '</media-poster><media-play-button class="own-video-big-play"><media-icon family="neutral" name="play" class="own-video-big-play-icon"></media-icon></media-play-button>',
);

// Alle id's van de skin en de verwijzingen ernaar ("vjs-<hash>-...").
const ids = [...new Set([...html.matchAll(/\bid="(vjs-[^"]+)"/g)].map((m) => m[1]))];
ids.forEach((id, index) => {
  html = html.replaceAll(`"${id}"`, `"{uid}-${index}"`);
});
if (/"vjs-/.test(html)) {
  throw new Error("Er staat nog een verwijzing naar een niet-hernoemd id in de template.");
}
if (/<slot/.test(html)) {
  throw new Error("Er staat nog een <slot> in de template.");
}

const out = path.join(root, "inc/functions/video-player/skin.html");
writeFileSync(
  out,
  `<!-- Gegenereerd door src/js/scripts/video-skin.mjs uit @videojs/html ${version} (neutral-video). Niet met de hand aanpassen. -->\n${html}\n`,
);
console.log(`skin.html geschreven (@videojs/html ${version}, ${ids.length} id's)`);
