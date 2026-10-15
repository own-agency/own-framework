<?php
defined('ABSPATH') || exit();

/**
 * Een uitgeschakeld post type blijft GEREGISTREERD, maar zonder enige
 * publieke of beheerkant. Bewust geen unregister_post_type(): blocks,
 * helpers en SCF-velden die get_post_type_object() aanroepen blijven dan
 * gewoon werken, en bestaande content blijft onaangeroerd in de database
 * staan — weer inschakelen zet alles precies terug.
 *
 * Wat uitschakelen doet, per laag:
 *   - front-end    niet publiek/queryable, geen archief, geen rewrite rules,
 *                  niet in zoeken of de sitemap → single en archief geven 404
 *   - beheer       geen menu, geen edit-scherm, niet in de REST API (dus ook
 *                  niet in de blockeditor), alle capabilities van het type
 *                  op do_not_allow — ook voor super admins
 *   - taxonomieën  die alleen bij uitgeschakelde types horen idem verborgen
 *   - blocks       weg uit de inserter, renderen niets op de front-end
 *   - overig       bijbehorende Thema opties-pagina en archive-/single-
 *                  templates verdwijnen uit het beheer
 */

/**
 * SCF registreert de CPT's uit acf-json; deze filter krijgt de uiteindelijke
 * register_post_type()-args.
 */
add_filter('acf/post_type/registration_args', function (array $args, array $post): array {
    if (!ownframework_post_type_disabled($post['post_type'] ?? '')) {
        return $args;
    }

    return array_merge($args, [
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => false,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'show_in_rest' => false,
        'has_archive' => false,
        'rewrite' => false,
        'query_var' => false,
    ]);
}, 10, 2);

// Priority 20: na inc/functions/vacature-taxonomy-settings.php, die show_ui
// per vacaturetaxonomie weer aan kan zetten.
add_filter('acf/taxonomy/registration_args', function (array $args, array $post): array {
    $object_types = array_filter((array) ($post['object_type'] ?? []));
    $disabled = ownframework_disabled_post_types();

    // Alleen als ALLE gekoppelde types uit staan — een taxonomie die ook bij
    // een actief type hoort, blijft voor dat type nodig.
    if (!$object_types || array_diff($object_types, $disabled)) {
        return $args;
    }

    return array_merge($args, [
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => false,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'show_in_rest' => false,
        'show_tagcloud' => false,
        'show_in_quick_edit' => false,
        'show_admin_column' => false,
        'rewrite' => false,
        'query_var' => false,
    ]);
}, 20, 2);

add_filter('acf/ui_options_page/registration_args', function (array $args): array {
    foreach (ownframework_disabled_post_types() as $post_type) {
        if (in_array($args['menu_slug'] ?? '', OWNFRAMEWORK_DEV_POST_TYPES[$post_type]['options_pages'], true)) {
            $args['capability'] = 'do_not_allow';
        }
    }
    return $args;
});

/**
 * show_ui => false houdt het menu en edit.php al dicht; dit sluit de rest
 * (post.php?action=edit via een oude link, verwijderen, bulkacties,
 * plugins die zelf capabilities checken). do_not_allow wint ook van
 * is_super_admin() in WP_User::has_cap().
 */
add_filter('map_meta_cap', function (array $caps, string $cap, int $user_id, array $args): array {
    $disabled = ownframework_disabled_post_types();
    if (!$disabled) {
        return $caps;
    }

    if (in_array($cap, ['edit_post', 'delete_post', 'publish_post'], true) && !empty($args[0])) {
        $post = get_post($args[0]);
        return $post && in_array($post->post_type, $disabled, true) ? ['do_not_allow'] : $caps;
    }

    // De eigen capabilities van het type (edit_verhalen, …). 'read' en alles
    // wat het type met gewone berichten deelt (een type zonder eigen
    // capability_type) blijven buiten schot.
    $post_object = get_post_type_object('post');
    $shared = $post_object ? array_values((array) $post_object->cap) : [];
    $shared[] = 'read';

    foreach ($disabled as $post_type) {
        $object = get_post_type_object($post_type);
        if ($object && in_array($cap, array_diff((array) $object->cap, $shared), true)) {
            return ['do_not_allow'];
        }
    }

    return $caps;
}, 10, 4);

/**
 * Blocks van een uitgeschakeld type: niet meer invoegbaar, en een instantie
 * die al op een pagina staat rendert niets (ook geen lege query/markup).
 */
function ownframework_disabled_blocks(): array
{
    $blocks = [];
    foreach (ownframework_disabled_post_types() as $post_type) {
        $blocks = array_merge($blocks, OWNFRAMEWORK_DEV_POST_TYPES[$post_type]['blocks']);
    }
    return $blocks;
}

add_filter('block_type_metadata', function (array $metadata): array {
    if (in_array($metadata['name'] ?? '', ownframework_disabled_blocks(), true)) {
        $metadata['supports']['inserter'] = false;
    }
    return $metadata;
});

add_filter('pre_render_block', function (?string $pre_render, array $parsed_block): ?string {
    if (in_array($parsed_block['blockName'] ?? '', ownframework_disabled_blocks(), true)) {
        return '';
    }
    return $pre_render;
}, 10, 2);

// archive-{type}/single-{type} uit de Site Editor-templatelijst.
add_filter('get_block_templates', function (array $templates): array {
    $disabled = ownframework_disabled_post_types();
    if (!$disabled) {
        return $templates;
    }

    $slugs = [];
    foreach ($disabled as $post_type) {
        $slugs[] = "archive-{$post_type}";
        $slugs[] = "single-{$post_type}";
    }

    return array_values(array_filter($templates, fn($template) => !in_array($template->slug, $slugs, true)));
});

/**
 * Archiefpagina's: een gewone pagina met het archive-{type}-sjabloon (zie
 * inc/functions/archive-template-post-states.php) is de vitrine van dat
 * archief. Staat het type uit, dan valt het echte archief weg en neemt die
 * pagina de URL over (/verhalen/) — dus die pagina moet mee uit.
 *
 * @return int[]
 */
function ownframework_disabled_archive_page_ids(): array
{
    static $cache = [];

    $blog_id = get_current_blog_id();
    if (isset($cache[$blog_id])) {
        return $cache[$blog_id];
    }

    $templates = array_map(fn($post_type) => "archive-{$post_type}", ownframework_disabled_post_types());

    return $cache[$blog_id] = $templates ? get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [['key' => '_wp_page_template', 'value' => $templates, 'compare' => 'IN']],
    ]) : [];
}

// Priority 0: vóór redirect_canonical (10), en vóór de block-template-loader
// het sjabloon kiest, zodat die 404.html pakt.
add_action('template_redirect', function (): void {
    if (!is_page() || !in_array(get_queried_object_id(), ownframework_disabled_archive_page_ids(), true)) {
        return;
    }

    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();

    // Anders "raadt" WP bij de 404 een bestaande URL met dezelfde slug — en
    // dat is precies deze pagina.
    add_filter('do_redirect_guess_404_permalink', '__return_false');
}, 0);

/**
 * Menu-items naar iets wat uit staat: de archiefpagina, het CPT-archief, een
 * los item, of een term van een verborgen taxonomie. Inclusief hun
 * submenu-items, anders toont de walker die als losse items op het hoogste
 * niveau. Alleen front-end: in Weergave → Menu's blijven ze zichtbaar.
 */
add_filter('wp_get_nav_menu_items', function (array $items): array {
    $disabled = ownframework_disabled_post_types();
    if (is_admin() || !$disabled) {
        return $items;
    }

    $pages = ownframework_disabled_archive_page_ids();
    $removed = [];

    foreach ($items as $item) {
        $hidden = match ($item->type) {
            'post_type' => in_array($item->object, $disabled, true)
                || ($item->object === 'page' && in_array((int) $item->object_id, $pages, true)),
            'post_type_archive' => in_array($item->object, $disabled, true),
            'taxonomy' => ($taxonomy = get_taxonomy($item->object)) && !$taxonomy->public && !$taxonomy->publicly_queryable,
            default => false,
        };
        if ($hidden) {
            $removed[(int) $item->ID] = true;
        }
    }

    do {
        $count = count($removed);
        foreach ($items as $item) {
            if (isset($removed[(int) $item->menu_item_parent])) {
                $removed[(int) $item->ID] = true;
            }
        }
    } while (count($removed) !== $count);

    return array_values(array_filter($items, fn($item) => !isset($removed[(int) $item->ID])));
});

// Niet in de sitemap en niet in de zoekresultaten.
add_filter('wp_sitemaps_posts_query_args', function (array $args, string $post_type): array {
    if ($post_type === 'page' && ($ids = ownframework_disabled_archive_page_ids())) {
        $args['post__not_in'] = array_merge($args['post__not_in'] ?? [], $ids);
    }
    return $args;
}, 10, 2);

add_action('pre_get_posts', function (WP_Query $query): void {
    if (!is_admin() && $query->is_search() && ($ids = ownframework_disabled_archive_page_ids())) {
        $query->set('post__not_in', array_merge((array) $query->get('post__not_in'), $ids));
    }
});

// Pagina's → Alle pagina's: laat zien waarom zo'n pagina een 404 geeft.
add_filter('display_post_states', function (array $post_states, WP_Post $post): array {
    if (in_array($post->ID, ownframework_disabled_archive_page_ids(), true)) {
        $post_states['own_archive_disabled'] = 'Contenttype uitgeschakeld (404)';
    }
    return $post_states;
}, 20, 2);
