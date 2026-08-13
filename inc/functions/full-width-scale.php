<?php
defined("ABSPATH") || exit();

/**
 * "Max. breedte 'Breed'" (Thema opties → Stijlen → Breedte, acf-json/group_
 * own_style_settings.json) — de derde, breedste van de drie uitlijnings-
 * niveaus ("Smal"/"Normaal"/"Breed", zie CLAUDE.md's "Alignment, width and
 * full-bleed backgrounds"), instelbaar per site. Standaard 1440px.
 *
 * Puur een CSS-variabele-override (--own-full-width op :root), zelfde patroon
 * als radius-scale.php's eigen redenering: theme.json heeft GEEN native
 * settings.layout-slot voor een derde breedtemaat (alleen contentSize/
 * wideSize bestaan), en geen enkel Ontwerp/Layout-veld leest een derde
 * waarde uit — dus is er niets natives om deze keuze ook via theme.json/een
 * editor-UI-veld te laten meelezen zoals bij "Smal"/"Normaal" (die WEL de
 * echte native contentSize/wideSize-instellingen zijn, rechtstreeks in
 * theme.json). Dit blijft daarom de enige — en beste beschikbare — manier om
 * de derde maat site-breed instelbaar te maken.
 *
 * Eén gedeelde getter (ownframework_get_full_width_max()) i.p.v. losse
 * get_field()-aanroepen: zowel deze CSS-override (src/css/app.css's
 * ".full-width"/".alignfull"-regels lezen "--own-full-width") als
 * inc/functions/enqueue-scripts-styles.php's eigen
 * ownframework_relabel_align_controls() (de "Breed"-toolbar-label, die de
 * waarde in de tekst zelf toont — Gutenberg's eigen "Max ... breed"-
 * toelichting bestaat structureel niet voor "full", zie CLAUDE.md Traps #26)
 * hebben exact dezelfde waarde-met-terugval nodig; twee keer diezelfde
 * get_field()+fallback-logica uitschrijven zou vroeg of laat uit sync kunnen
 * raken.
 */
function ownframework_get_full_width_max(): int
{
    $width_field = get_field("width", "option") ?: [];
    $max_width = (int) ($width_field["full"] ?? 1440);

    return $max_width > 0 ? $max_width : 1440;
}

function ownframework_full_width_css(): string
{
    $max_width = ownframework_get_full_width_max();

    return ":root{--own-full-width:{$max_width}px;}";
}

// Later priority dan ownframework_enqueue_assets()/ownframework_enqueue_
// editor_assets() (inc/functions/enqueue-scripts-styles.php, beide op
// standaard prioriteit 10): wp_add_inline_style() heeft het doel-handle al
// geregistreerd nodig, en deze twee losse hooks houden dit bestand
// onafhankelijk van dat andere bestand i.p.v. daar zelf in te moeten haken.
add_action("wp_enqueue_scripts", function () {
    wp_add_inline_style("theme-styles", ownframework_full_width_css());
}, 20);

add_action("enqueue_block_editor_assets", function () {
    wp_add_inline_style("theme-styles-editor", ownframework_full_width_css());
}, 20);
