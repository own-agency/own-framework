<?php
defined("ABSPATH") || exit();

/**
 * "Kleur bediening" (Thema opties → Stijlen → Videospeler,
 * acf-json/group_own_style_settings.json) — een site-brede accentkleur voor
 * Plyr's bediening (src/css/components/video.css's "--plyr-color-main"),
 * gekozen via het bestaande "own_color_palette"-veldtype (inc/scf/
 * custom-color-palette.php, class Own_Field_Color_Palette) i.p.v. een losse
 * hexcode: dat veld wijst altijd naar hetzelfde live Ontwerp-kleurenpalet dat
 * Gutenberg's eigen kleurenkiezers gebruiken (root CLAUDE.md's "Brand
 * identity"-sectie), dus blijft dit gekoppeld aan de kleuren die een klant
 * zelf via Ontwerp beheert, i.p.v. een vaste losse waarde. "return_format:
 * css_var" geeft al direct "var(--wp--preset--color--<slug>)" (of de kale
 * hex/het keyword voor wit/transparant/zwart) terug, niets hier hoeft dat
 * zelf op te bouwen.
 *
 * "Geen kleur" (lege waarde, dit veld's eigen default) betekent bewust "volg
 * Primair" — zodat een site zonder expliciete keuze hier precies hetzelfde
 * oogt als vóór deze instelling bestond (dezelfde reden als radius-scale.
 * php's "medium"-default).
 */
function ownframework_video_player_color_css(): string
{
    $color = get_field("video_player", "option")["color"] ?? "";
    $value = $color !== "" ? $color : "var(--wp--preset--color--primary)";

    return ":root{--own-video-player-color:{$value};}";
}

// Zelfde reden als radius-scale.php's eigen late prioriteit: wp_add_inline_
// style() heeft "theme-styles" al geregistreerd nodig (ownframework_enqueue_
// assets(), inc/functions/enqueue-scripts-styles.php, prioriteit 10).
// Alleen front-end: Plyr initialiseert alleen daar (src/js/main.js), niet in
// de editor (src/js/editor-scripts.js) — zie video.js's eigen docblok.
add_action("wp_enqueue_scripts", function () {
    wp_add_inline_style("theme-styles", ownframework_video_player_color_css());
}, 20);
