<?php
defined("ABSPATH") || exit();

/**
 * --own-h1-font-size … --own-h6-font-size (+ -line-height) op :root: de
 * koppen-typografie zoals ingesteld in Ontwerp (Global Styles → Typografie →
 * Koppen).
 *
 * WordPress zet die alleen als regel op het ELEMENT (h3 {…}), niet als
 * variabele. Een kaarttitel die als h2 gerenderd wordt, of een kop-achtig
 * element dat geen heading is, kan daar dus niet bij — met deze variabelen
 * wel, en een wijziging in Ontwerp loopt dan vanzelf mee.
 *
 * Gebruik met "!" (text-(length:--own-h3-font-size)!): de theme.json-regels
 * voor h1–h6 zijn ongelaagd en winnen anders van élke Tailwind-utility
 * (@layer utilities), ongeacht specificiteit (CLAUDE.md Traps #34).
 */
function ownframework_heading_size_css(): string
{
    // Vrije invoer uit Ontwerp: niets wat de declaratie kan breken.
    $clean = fn(string $value): string => trim(str_replace([";", "{", "}", "<", ">"], "", $value));

    $declarations = "";
    foreach (range(1, 6) as $level) {
        $typography = wp_get_global_styles(["elements", "h{$level}", "typography"]);

        $font_size = $typography["fontSize"] ?? null;
        if (is_string($font_size) && $font_size !== "") {
            // "var:preset|font-size|large" → "var(--wp--preset--font-size--large)",
            // zoals WP_Theme_JSON dat voor de h-regel zelf doet. Bewust geen
            // "resolve-variables"-transform: die schrijft de uitgerekende
            // clamp() uit in plaats van naar de preset te verwijzen.
            if (str_starts_with($font_size, "var:")) {
                $font_size = "var(--wp--" . str_replace("|", "--", substr($font_size, 4)) . ")";
            }
            $declarations .= "--own-h{$level}-font-size:{$clean($font_size)};";
        }

        $line_height = $typography["lineHeight"] ?? null;
        if (is_scalar($line_height) && (string) $line_height !== "") {
            $declarations .= "--own-h{$level}-line-height:{$clean((string) $line_height)};";
        }
    }

    return ":root{{$declarations}}";
}

// Priority 20: zie radius-scale.php — de handles moeten al geregistreerd zijn.
add_action("wp_enqueue_scripts", function () {
    wp_add_inline_style("theme-styles", ownframework_heading_size_css());
}, 20);

add_action("enqueue_block_editor_assets", function () {
    wp_add_inline_style("theme-styles-editor", ownframework_heading_size_css());
}, 20);
