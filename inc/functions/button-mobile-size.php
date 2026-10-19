<?php
defined("ABSPATH") || exit();

/**
 * Knoppen op telefoons (< 640px) iets kleiner: de padding en lettergrootte
 * uit Ontwerp, geschaald met een vaste factor. Wat in Ontwerp staat blijft de
 * bron; op een site met andere knopinstellingen schaalt dit gewoon mee in
 * plaats van een eigen maat op te leggen.
 *
 * Ontwerp kent drie lagen, en WordPress past ze in deze volgorde toe — dit
 * bestand doet hetzelfde, elke laag valt terug op de vorige voor wat hij zelf
 * niet instelt:
 *   1. Elementen → Knop (alle knoppen, ook die van het thema en Forminator)
 *   2. Blokken → Knop (core/button)
 *   3. de stijlvarianten van core/button (bv. "Outline"), die hun eigen kopie
 *      van padding/lettergrootte meedragen
 * Alleen laag 1 schalen zou laag 2/3 overschrijven met de (grotere) element-
 * waarden — een knop werd op mobiel dan juist breder.
 *
 * Instellingen per knop in de editor (eigen padding of lettergrootte) blijven
 * winnen: die staan inline of als preset-klasse met !important. Bewust niet in
 * de editor: daar toont het canvas de knop precies zoals Ontwerp hem instelt.
 *
 * ":root body :where(…)": WordPress zet zijn knopregels als ":root :where(…)"
 * (specificiteit 0,1,0) en de volgorde van die stylesheet t.o.v. de onze ligt
 * niet vast. Eén element erbij wint altijd, zonder !important. Onderling
 * hebben de drie regels dezelfde specificiteit, dus de bronvolgorde hieronder
 * (algemeen → specifiek) beslist.
 */
const OWNFRAMEWORK_BUTTON_MOBILE_PADDING_SCALE = 0.75;
const OWNFRAMEWORK_BUTTON_MOBILE_FONT_SCALE = 0.9375;

/**
 * Padding per kant + lettergrootte uit één Ontwerp-laag. Een padding als los
 * shorthand-string ("10px 18px") wordt overgeslagen: Ontwerp slaat per kant op.
 *
 * @return array<string, string> bv. ["padding-top" => "10px", "font-size" => "16px"]
 */
function ownframework_button_mobile_size_values(array $styles): array
{
    $values = [];
    $padding = $styles["spacing"]["padding"] ?? null;
    if (is_array($padding)) {
        foreach (["top", "right", "bottom", "left"] as $side) {
            if (is_string($padding[$side] ?? null) && $padding[$side] !== "") {
                $values["padding-{$side}"] = $padding[$side];
            }
        }
    }
    $font_size = $styles["typography"]["fontSize"] ?? null;
    if (is_string($font_size) && $font_size !== "") {
        $values["font-size"] = $font_size;
    }
    return $values;
}

function ownframework_button_mobile_size_css(): string
{
    // Vrije invoer uit Ontwerp: niets wat de declaratie kan breken.
    $clean = fn(string $value): string => trim(str_replace([";", "{", "}", "<", ">"], "", $value));

    // resolve-variables: presets ("var:preset|spacing|xs") komen uitgeschreven
    // terug (clamp(), px), zodat calc() ermee kan rekenen.
    $resolve = ["transforms" => ["resolve-variables"]];
    $element = ownframework_button_mobile_size_values(wp_get_global_styles(["elements", "button"], $resolve));
    $block_styles = wp_get_global_styles(["blocks", "core/button"], $resolve);
    $block = array_merge($element, ownframework_button_mobile_size_values($block_styles));

    $layers = [".wp-element-button, .wp-block-button__link" => $element];
    $layers[".wp-block-button .wp-block-button__link"] = $block;
    foreach ((array) ($block_styles["variations"] ?? []) as $name => $variation) {
        if (is_array($variation) && preg_match('/^[a-z0-9-]+$/', (string) $name)) {
            $layers[".wp-block-button.is-style-{$name} .wp-block-button__link"] =
                array_merge($block, ownframework_button_mobile_size_values($variation));
        }
    }

    $rules = "";
    foreach ($layers as $selector => $values) {
        $declarations = "";
        foreach ($values as $property => $value) {
            $scale = $property === "font-size"
                ? OWNFRAMEWORK_BUTTON_MOBILE_FONT_SCALE
                : OWNFRAMEWORK_BUTTON_MOBILE_PADDING_SCALE;
            $declarations .= "{$property}:calc({$clean($value)} * {$scale});";
        }
        if ($declarations !== "") {
            $rules .= ":root body :where({$selector}){{$declarations}}";
        }
    }

    return $rules === "" ? "" : "@media (width < 40rem){{$rules}}";
}

// Priority 20: zie radius-scale.php — de handle moet al geregistreerd zijn.
add_action("wp_enqueue_scripts", function () {
    $css = ownframework_button_mobile_size_css();
    if ($css !== "") {
        wp_add_inline_style("theme-styles", $css);
    }
}, 20);
