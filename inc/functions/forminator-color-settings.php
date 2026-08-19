<?php
defined('ABSPATH') || exit();

/**
 * "Formulieren" options page (Thema opties → Formulieren, acf-json/group_
 * own_forminator_settings.json) — three colours (Actief/Fout/Succes) for
 * src/css/components/forminator.css's own --forminator-primary/-error/
 * -success variables, grouped under one "Kleuren" group field — same
 * organisation as Thema opties → Live search's own "colors" group (acf-json/
 * group_own_live_search_settings.json, read the identical way by blocks/
 * header/parts/search-modal.php: get_field('colors', 'option') returns the
 * whole group, individual colours read off that array by their own name).
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
    $colors = get_field('colors', 'option') ?: [];

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
