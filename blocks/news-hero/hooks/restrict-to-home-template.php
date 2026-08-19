<?php
defined('ABSPATH') || exit();

/**
 * acf/news-hero en acf/news horen nergens anders te bestaan dan in
 * templates/home.html — acf/news bindt aan de globale $store.newsFilters
 * (src/js/scripts/news-filter.js) en de eigen WP_Query/REST-koppeling
 * (blocks/news/hooks/query.php) verwacht de nieuwsarchiefcontext, niet een
 * willekeurige pagina. Verbergt ze daarom uit de inserter overal BEHALVE als
 * je die ene template zelf bewerkt — zelfde patroon als blocks/agenda-hero/
 * hooks/restrict-to-archive-agenda-template.php.
 *
 * post_name (niet een ID): zolang niemand de template ooit aanpast heeft
 * templates/home.html geen eigen rij in de database — WordPress synthetiseert
 * dan een virtuele wp_template-post met post_name afgeleid van de
 * bestandsnaam. Zodra een editor 'm wel aanpast, krijgt de ECHTE, opgeslagen
 * versie dezelfde slug.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/news-hero', 'acf/news'];

    $post = $editor_context->post ?? null;
    $is_home_template = $post
        && $post->post_type === 'wp_template'
        && $post->post_name === 'home';

    if ($is_home_template) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
