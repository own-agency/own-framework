<?php
defined("ABSPATH") || exit();

/**
 * "Hoekafronding" (Thema opties → Stijlen → Radius, acf-json/group_own_
 * style_settings.json) — een algehele stijlkeuze voor de hele site, niet
 * iets per block. Tailwind v4's eigen "rounded-*"-utilities lezen hun waarde
 * al via var(--radius-lg) etc. i.p.v. een vaste waarde per class te bakken
 * (gecontroleerd in de gecompileerde CSS) — deze functie overschrijft precies
 * díe custom properties op :root, dus ELKE "rounded-*"-klasse in het hele
 * theme (nu en toekomstig, geen block hoeft hiervoor aangepast te worden)
 * volgt automatisch mee. "rounded-full" (knoppen/avatars) is in Tailwind
 * zelf altijd een vaste, extreem grote pixelwaarde (geen var()) — blijft dus
 * altijd een pil/cirkel, ongeacht deze instelling.
 *
 * Puur een CSS-variabele-override, geen theme.json-injectie zoals spacing-
 * scale.php's eigen "Ruimteschaal" — theme.json heeft geen preset-schaal
 * voor border-radius (Gutenberg's eigen Radius-control in het Rand-paneel is
 * een vrije schuifregelaar, geen keuzelijst), dus is er niets natives om deze
 * keuze ook ALS keuze in de editor te laten meelezen. Dit blijft daarom de
 * enige — en beste beschikbare — manier om dit site-breed te sturen.
 */
function ownframework_radius_scale_css(): string
{
    $radius_field = get_field("radius", "option") ?: [];
    $scale = $radius_field["scale"] ?? "medium";

    $values = match ($scale) {
        "none" => ["sm" => "0", "md" => "0", "lg" => "0", "xl" => "0", "2xl" => "0", "3xl" => "0"],
        "small" => ["sm" => "0.125rem", "md" => "0.1875rem", "lg" => "0.25rem", "xl" => "0.375rem", "2xl" => "0.5rem", "3xl" => "0.75rem"],
        "large" => ["sm" => "0.375rem", "md" => "0.5rem", "lg" => "0.75rem", "xl" => "1rem", "2xl" => "1.5rem", "3xl" => "2rem"],
        // "medium": Tailwind's eigen standaardschaal, ongewijzigd — de default
        // hierboven (ACF-veld EN deze match) zodat een site zonder expliciete
        // keuze precies hetzelfde blijft ogen als vóór deze instelling bestond.
        default => ["sm" => "0.25rem", "md" => "0.375rem", "lg" => "0.5rem", "xl" => "0.75rem", "2xl" => "1rem", "3xl" => "1.5rem"],
    };

    $declarations = "";
    foreach ($values as $step => $value) {
        $declarations .= "--radius-{$step}:{$value};";
    }

    return ":root{{$declarations}}";
}

// Later priority dan ownframework_enqueue_assets()/ownframework_enqueue_
// editor_assets() (inc/functions/enqueue-scripts-styles.php, beide op
// standaard prioriteit 10): wp_add_inline_style() heeft het doel-handle al
// geregistreerd nodig, en deze twee losse hooks houden dit bestand
// onafhankelijk van dat andere bestand i.p.v. daar zelf in te moeten haken.
add_action("wp_enqueue_scripts", function () {
    wp_add_inline_style("theme-styles", ownframework_radius_scale_css());
}, 20);

add_action("enqueue_block_editor_assets", function () {
    wp_add_inline_style("theme-styles-editor", ownframework_radius_scale_css());
}, 20);
