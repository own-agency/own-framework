<?php
defined('ABSPATH') || exit();

/**
 * Two layers, same goal: a Forminator button should look like — and stay in
 * sync with — every native "Knop" on the site (theme.json's styles.
 * elements.button), not a second, hand-copied button design.
 *
 * 1. Class injection (below): adds "wp-block-button__link wp-element-button"
 *    to Forminator's own <button> markup, the same class pair CLAUDE.md
 *    documents for acf/vacature-apply's/acf/agenda-share's own apply/
 *    signup buttons. On its own this ONLY wins visually if WP core's global-
 *    styles CSS (a plain .wp-element-button class selector) happens to load
 *    AFTER Forminator's own default form CSS in the cascade — not
 *    guaranteed, since both are equal-specificity class selectors and
 *    enqueue order between a theme and a plugin isn't something to rely on.
 * 2. ownframework_forminator_button_css() (below): reads the SAME live,
 *    Ontwerp-editable button style via wp_get_global_styles() — not a
 *    hardcoded copy of theme.json's file, the actual resolved value,
 *    including any customization a client has made via Site Editor → Stijlen
 *    → Elementen → Knoppen — and writes it into CSS custom properties that
 *    src/css/components/forminator.css's own .forminator-button rule reads
 *    with !important, so it wins over Forminator's own default styling
 *    regardless of load order. This is the layer that actually guarantees
 *    the visual result; the class injection is additive (matches DevTools
 *    expectations, costs nothing) but not load-bearing on its own.
 */
add_filter('do_shortcode_tag', function ($output, $tag) {
    if ('forminator_form' !== $tag || false === strpos($output, 'forminator-button')) {
        return $output;
    }

    return preg_replace(
        '/class=(["\'])forminator-button\b/',
        'class=$1wp-block-button__link wp-element-button forminator-button',
        $output
    );
}, 10, 2);

/**
 * wp_get_global_styles(['elements','button',…]) alone isn't the whole
 * picture: theme.json's own styles.blocks['core/button'] can — and on this
 * site, does — override it for an ACTUAL Button block instance (verified
 * against the site's real compiled global-styles CSS: a distinct, per-
 * corner border-radius and fixed-pixel padding live only on the "blocks.
 * core/button" selector, a real Button on the page never falls back to the
 * plainer "elements.button" pill/preset-padding at all). theme.json's OWN
 * data keeps these as two SEPARATE branches — WP's merge engine doesn't
 * splice block-level overrides into the elements-level data the way the
 * BROWSER's cascade visually combines them — so reading only "elements"
 * silently missed whatever a client had customized specifically for
 * Button blocks via Site Editor → Stijlen → Blokken → Knop (a more
 * specific panel than the site-wide Stijlen → Elementen → Knoppen one).
 * ownframework_forminator_global_style() below replicates that same
 * "block-level wins, element-level is the fallback for whatever it didn't
 * touch" relationship in PHP, per property, instead of only ever reading
 * one of the two trees.
 */
function ownframework_forminator_global_style(array $property_path)
{
    $value = wp_get_global_styles(array_merge(['blocks', 'core/button'], $property_path));
    if ((is_string($value) && $value !== '') || is_array($value)) {
        return $value;
    }

    return wp_get_global_styles(array_merge(['elements', 'button'], $property_path));
}

/**
 * border.radius can be a single string (uniform) OR, once a client sets
 * per-corner values via Site Editor's own corner-radius control (exactly
 * what this site's Button block style uses), an array keyed topLeft/
 * topRight/bottomRight/bottomLeft — CSS's own border-radius shorthand order
 * is TL TR BR BL, not the object's own key order, so this can't just
 * implode() the array's values as-is.
 */
function ownframework_forminator_button_radius(): string
{
    $radius = ownframework_forminator_global_style(['border', 'radius']);

    if (is_string($radius)) {
        return $radius;
    }

    if (is_array($radius)) {
        $corner = fn ($key) => $radius[$key] ?? '0';
        return sprintf('%s %s %s %s', $corner('topLeft'), $corner('topRight'), $corner('bottomRight'), $corner('bottomLeft'));
    }

    return '';
}

function ownframework_forminator_button_css(): string
{
    $map = [
        '--forminator-native-button-bg' => ['color', 'background'],
        '--forminator-native-button-text' => ['color', 'text'],
        '--forminator-native-button-fontfamily' => ['typography', 'fontFamily'],
        '--forminator-native-button-fontsize' => ['typography', 'fontSize'],
        '--forminator-native-button-weight' => ['typography', 'fontWeight'],
        '--forminator-native-button-padding-block' => ['spacing', 'padding', 'top'],
        '--forminator-native-button-padding-inline' => ['spacing', 'padding', 'right'],
    ];

    $declarations = '';
    foreach ($map as $css_var => $path) {
        $value = ownframework_forminator_global_style($path);
        if (is_string($value) && $value !== '') {
            $declarations .= "{$css_var}:{$value};";
        }
    }

    $radius = ownframework_forminator_button_radius();
    if ($radius !== '') {
        $declarations .= "--forminator-native-button-radius:{$radius};";
    }

    return $declarations === '' ? '' : ":root{{$declarations}}";
}

// Same later-priority reasoning as radius-scale.php: wp_add_inline_style()
// needs its target handle already registered, so this stays independent of
// ownframework_enqueue_assets()/ownframework_enqueue_editor_assets()
// (inc/functions/enqueue-scripts-styles.php) rather than hooking in there.
add_action('wp_enqueue_scripts', function () {
    $css = ownframework_forminator_button_css();
    if ($css !== '') {
        wp_add_inline_style('theme-styles', $css);
    }
}, 20);

add_action('enqueue_block_editor_assets', function () {
    $css = ownframework_forminator_button_css();
    if ($css !== '') {
        wp_add_inline_style('theme-styles-editor', $css);
    }
}, 20);
