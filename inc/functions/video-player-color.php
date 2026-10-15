<?php
defined("ABSPATH") || exit();

/**
 * "Kleur bediening" (Thema opties → Stijlen → Videospeler,
 * acf-json/group_own_style_settings.json) — een site-brede accentkleur voor
 * de Video.js-bediening (src/css/components/video.css's "--media-accent-color"),
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

    // Video.js leidt de tekstkleur óp de accentkleur zelf af via CSS
    // contrast-color(), maar dat kent Safari pas vanaf 26 / Chrome vanaf 147 —
    // daaronder valt de skin terug op een vaste tekstkleur die alleen bij zijn
    // eigen standaard-accent past. Dus hier zelf bepalen, via de hexwaarde
    // achter de gekozen palet-slug.
    $hex = $value;
    if (preg_match("/--wp--preset--color--([a-z0-9-]+)/", $value, $match)) {
        $hex = ownframework_get_theme_json_colors()[$match[1]]["color"] ?? $value;
    }
    $text = ownframework_color_is_light($hex) ? "#000" : "#fff";

    return ":root{--own-video-player-color:{$value};--own-video-player-text-color:{$text};}";
}

// Zelfde reden als radius-scale.php's eigen late prioriteit: wp_add_inline_
// style() heeft "theme-styles" al geregistreerd nodig (ownframework_enqueue_
// assets(), inc/functions/enqueue-scripts-styles.php, prioriteit 10).
// Alleen front-end: de speler bestaat alleen daar (inc/functions/
// video-player.php's render_block-filter), niet in de editor.
add_action("wp_enqueue_scripts", function () {
    wp_add_inline_style("theme-styles", ownframework_video_player_color_css());
}, 20);
