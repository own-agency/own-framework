<?php

/**
 * Add theme colors to the TinyMCE (WYSIWYG field) color picker.
 */

/**
 * The resolved colour palette, via wp_get_global_settings() — the same
 * source Gutenberg's own colour pickers read. This reflects whatever a
 * client has set through the Site Editor's Ontwerp panel (Global Styles),
 * not just theme.json's own file content.
 *
 * @return array<string, array{name: string, color: string}> Keyed by slug.
 */
function ownframework_get_theme_json_colors()
{
    static $colors = null;
    if ($colors !== null) {
        return $colors;
    }

    $colors = [];

    // wp_get_global_settings(['color', 'palette']) does NOT return a flat
    // list — confirmed by querying a real site directly (wp eval) after this
    // helper silently returned nothing despite a populated palette: it
    // returns entries GROUPED BY ORIGIN, e.g. ['default' => [...WP's own 12
    // built-ins...], 'theme' => [...theme.json's palette...], 'custom' =>
    // [...added via Ontwerp...]]. A plain foreach over that top-level array
    // iterates the 3 ORIGIN ARRAYS themselves (each one a full list of
    // colour entries), not individual {slug, color} entries — so
    // isset($entry['slug'], $entry['color']) was never true for any of
    // them, on every site, regardless of whether custom colours existed.
    // 'default' is skipped deliberately, not merged in: theme.json sets
    // settings.color.defaultPalette to false specifically so WP's own 12
    // generic swatches don't clutter the picker (root CLAUDE.md's "Brand
    // identity" section) — that flag only suppresses them in the block
    // editor's OWN merge logic, not in this raw PHP accessor, so skipping it
    // here too is what actually keeps this helper consistent with what an
    // editor sees in Ontwerp. 'custom' merges AFTER 'theme' (and wins on a
    // slug collision) to match WP's own default < theme < custom precedence.
    $palette = wp_get_global_settings(['color', 'palette']) ?: [];

    foreach (['theme', 'custom'] as $origin) {
        foreach ($palette[$origin] ?? [] as $entry) {
            if (isset($entry['slug'], $entry['color'])) {
                $colors[$entry['slug']] = [
                    'name'  => $entry['name'] ?? $entry['slug'],
                    'color' => $entry['color'],
                ];
            }
        }
    }

    return $colors;
}

function ownframework_add_custom_colors_to_tinymce($init)
{
    $theme_colors = ownframework_get_theme_json_colors();

    if (empty($theme_colors)) {
        return $init;
    }

    $custom_colors = [];

    // Format colors for TinyMCE: a flat [hex, label, hex, label, ...] list.
    foreach ($theme_colors as $entry) {
        $custom_colors[] = ltrim($entry['color'], '#');
        $custom_colors[] = $entry['name'];
    }

    $init['textcolor_map'] = json_encode($custom_colors);
    $init['textcolor_cols'] = 5;

    return $init;
}
add_filter('tiny_mce_before_init', 'ownframework_add_custom_colors_to_tinymce');
