<?php
defined('ABSPATH') || exit();

/**
 * Thema opties → Vacatures (acf-json/ui_options_page_own_vacature_
 * taxonomies.json + group_own_vacature_taxonomies.json — this options page
 * also holds group_own_vacature_application.json's "Sollicitatiemethode"
 * settings, sharing the same page rather than its own separate one) lets a
 * site turn each non-default taxonomy on the vacancy post type on/off and
 * give it its own SVG icon. blocks/vacatures already discovers its taxonomies
 * dynamically
 * (get_object_taxonomies('vacancy'), see hooks/query.php) rather than
 * hardcoding "dienstverband" — a disabled taxonomy only needs to disappear
 * from THAT discovery, which show_ui already drives, so turning show_ui (and
 * every other admin/front-end surface) off below is enough: no separate
 * "is this taxonomy enabled" check is needed anywhere else in the block.
 */

const OWNFRAMEWORK_VACATURE_TAXONOMY_DEFAULT_ENABLED = [
    'dienstverband' => true,
    'salaris' => false,
    'opleidingsniveau' => false,
    'fte' => false,
    'locatie' => false,
    'voorwaarde' => false,
    'school' => false,
];

/**
 * Reads the raw option value directly rather than get_field() — this runs
 * from acf/taxonomy/registration_args, fired via register_taxonomy_for_object_
 * type() on the acf/init action at priority 6. get_field('option') isn't
 * reliably ready that early in the request (see the Koppelingen credentials
 * section of CLAUDE.md for the same trap with acf_update_setting()), while a
 * plain get_option() is always safe — the "group" ACF field type stores each
 * sub-field under its own option row prefixed with the group's own field name
 * (secure-custom-fields/includes/fields/class-acf-field-group.php's
 * prepare_field_for_db()), so "enabled" under the "dienstverband" group is
 * "options_dienstverband_enabled".
 */
function ownframework_vacature_taxonomy_enabled(string $taxonomy): bool
{
    $default = OWNFRAMEWORK_VACATURE_TAXONOMY_DEFAULT_ENABLED[$taxonomy] ?? false;
    $stored = get_option("options_{$taxonomy}_enabled", null);

    return $stored === null ? $default : (bool) $stored;
}

/**
 * Safe to use get_field() here — unlike the registration-time check above,
 * every caller (blocks/vacatures/hooks/query.php, parts/card.php) runs during
 * a normal front-end render or REST request, long after ACF is fully loaded.
 */
function ownframework_vacature_taxonomy_icon(string $taxonomy): string
{
    if (!array_key_exists($taxonomy, OWNFRAMEWORK_VACATURE_TAXONOMY_DEFAULT_ENABLED)) {
        return '';
    }

    $settings = get_field($taxonomy, 'option');

    return is_array($settings) ? (string) ($settings['icon'] ?? '') : '';
}

add_filter('acf/taxonomy/registration_args', function (array $args, array $post): array {
    $taxonomy = $post['taxonomy'] ?? '';

    if (!array_key_exists($taxonomy, OWNFRAMEWORK_VACATURE_TAXONOMY_DEFAULT_ENABLED)) {
        return $args;
    }

    if (ownframework_vacature_taxonomy_enabled($taxonomy)) {
        return $args;
    }

    $args['public'] = false;
    $args['publicly_queryable'] = false;
    $args['show_ui'] = false;
    $args['show_in_menu'] = false;
    $args['show_in_nav_menus'] = false;
    $args['show_in_rest'] = false;
    $args['show_tagcloud'] = false;
    $args['show_in_quick_edit'] = false;
    $args['show_admin_column'] = false;

    return $args;
}, 10, 2);

/**
 * Sanitizes the raw SVG a site admin pastes into an icon field, same
 * allowlist shape as a typical hand-rolled icon set (path/g/rect/circle/line/
 * polygon/polyline/ellipse) — echoed unescaped wherever a taxonomy icon is
 * rendered (parts/filter-groups.php, parts/card.php), so it has to be made
 * safe here, on save, rather than trusted at output time.
 */
add_filter('acf/update_value', function ($value, $post_id, array $field) {
    $key = $field['key'] ?? '';

    if (!str_starts_with($key, 'field_own_vacature_tax_') || !str_ends_with($key, '_icon')) {
        return $value;
    }

    if (!is_string($value) || $value === '') {
        return $value;
    }

    return wp_kses($value, [
        'svg' => [
            'xmlns' => true,
            'viewbox' => true,
            'width' => true,
            'height' => true,
            'fill' => true,
            'class' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
        ],
        'path' => [
            'd' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
        ],
        'g' => [
            'fill' => true,
            'stroke' => true,
            'transform' => true,
        ],
        'rect' => [
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'rx' => true,
            'ry' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
        ],
        'circle' => [
            'cx' => true,
            'cy' => true,
            'r' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
        ],
        'ellipse' => [
            'cx' => true,
            'cy' => true,
            'rx' => true,
            'ry' => true,
            'fill' => true,
            'stroke' => true,
        ],
        'line' => [
            'x1' => true,
            'y1' => true,
            'x2' => true,
            'y2' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
        ],
        'polygon' => [
            'points' => true,
            'fill' => true,
            'stroke' => true,
        ],
        'polyline' => [
            'points' => true,
            'fill' => true,
            'stroke' => true,
        ],
    ]);
}, 10, 3);
