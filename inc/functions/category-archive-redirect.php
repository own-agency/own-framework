<?php
defined('ABSPATH') || exit();

/**
 * Categorie-archieven (/category/slug/) hebben geen eigen template — ze
 * vielen terug op templates/index.html's kale, ongestylede Query Loop, die
 * niets weet van blocks/news/hooks/query.php's eigen filter/tax_query-
 * koppeling (die bewust beperkt is tot is_home(), zie dat bestand). In
 * plaats van een tweede, parallelle archiefweergave te bouwen stuurt een
 * categorie-URL door naar de bestaande nieuwsarchiefpagina met diezelfde
 * categorie al actief in het filter — dezelfde "category[]=slug"-vorm die
 * src/js/scripts/news-filter.js zelf ook genereert, dus de bestaande
 * filter-UI (server-side render én client-side Alpine-state) pakt 'm zonder
 * verdere aanpassing op.
 */
add_action('template_redirect', function () {
    if (!is_category()) {
        return;
    }

    $term = get_queried_object();
    if (!($term instanceof WP_Term)) {
        return;
    }

    $posts_page_id = (int) get_option('page_for_posts');
    $archive_url = $posts_page_id ? get_permalink($posts_page_id) : home_url('/');

    wp_safe_redirect(add_query_arg(['category' => [$term->slug]], $archive_url), 301);
    exit();
});
