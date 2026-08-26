<?php
defined('ABSPATH') || exit();

/**
 * "Formulieren" options page (Thema opties → Formulieren, acf-json/group_
 * own_forminator_settings.json) — three colours (Actief/Fout/Succes) for
 * src/css/components/forminator.css's own --forminator-primary/-error/
 * -success variables, grouped under one "Kleuren" group field, name
 * "forminator_colors" — same organisation as Thema opties → Live search's/
 * Melding(en)'s own Kleuren group, each with ITS OWN page-unique name
 * ("live_search_colors"/"notifications_colors"). All 3 used to share the
 * literal name "colors", which cross-contaminated every page's colours with
 * each other's: SCF's runtime "values" store caches a loaded value under
 * "{$post_id}:{$field['name']}" (acf_get_value(), includes/
 * acf-value-functions.php) — a bare NAME, not the field key, and not scoped
 * per field group — so on any request where more than one of the 3 loads
 * (this hook + search-modal.php's and notifications/includes/render.php's
 * own colours all run on every front-end page load), whichever loaded FIRST
 * won the single shared cache slot for the rest of the request; the other 2
 * silently got its colours back instead of their own. Each page's own group
 * field now has a page-unique name, which is the actual fix. get_field() is
 * still called by field KEY (not name) below, as an extra guard against a
 * milder, SEPARATE ambiguity (which field DEFINITION a bare name resolves
 * to) — that alone would NOT have fixed the cache collision above, since the
 * cache key is always the field's raw name regardless of which selector
 * get_field() is called with. Individual colours are read off the returned
 * group array by their own (already page-prefixed) name, unchanged.
 * Each field uses own_color_palette with return_format=css_var, so an editor
 * picks a real swatch from the site's Ontwerp palette (or white/black/
 * transparent/a custom colour) rather than an arbitrary hex disconnected
 * from it — get_field() then already returns a ready-to-use
 * "var(--wp--preset--color--…)" string (or a literal hex), so this only
 * needs to write it straight into a :root override, same pattern radius-
 * scale.php already uses for --radius-*. An empty field (no colour chosen)
 * is skipped entirely, leaving forminator.css's own default ("geen kleur" =
 * Primair uit Ontwerp, or the hardcoded red/green for error/success) in
 * effect.
 */
function ownframework_forminator_color_settings_css(): string
{
    $colors = get_field('field_own_forminator_colors', 'option') ?: [];

    $map = [
        'forminator_active_color' => '--forminator-primary',
        'forminator_error_color' => '--forminator-error',
        'forminator_success_color' => '--forminator-success',
    ];

    $declarations = '';
    foreach ($map as $field_name => $css_var) {
        $value = $colors[$field_name] ?? '';
        if (!empty($value)) {
            $declarations .= "{$css_var}:{$value};";
        }
    }

    return $declarations === '' ? '' : ":root{{$declarations}}";
}

// Same later-priority reasoning as radius-scale.php: wp_add_inline_style()
// needs its target handle already registered, so this stays independent of
// ownframework_enqueue_assets()/ownframework_enqueue_editor_assets()
// (inc/functions/enqueue-scripts-styles.php) rather than hooking in there.
add_action('wp_enqueue_scripts', function () {
    $css = ownframework_forminator_color_settings_css();
    if ($css !== '') {
        wp_add_inline_style('theme-styles', $css);
    }
}, 20);

add_action('enqueue_block_editor_assets', function () {
    $css = ownframework_forminator_color_settings_css();
    if ($css !== '') {
        wp_add_inline_style('theme-styles-editor', $css);
    }
}, 20);
