<?php
defined('ABSPATH') || exit();

/**
 * acf/news-single-hero leest per-post data van "het huidige bericht"
 * (get_the_title()/get_the_date()/get_the_category(), via get_the_ID()) —
 * buiten een echte `post`-context is dat geen zinnige plek. Twee plekken
 * zijn geldig, zelfde patroon als blocks/agenda-aanmelden/hooks/
 * restrict-to-agenda-context.php (nu verwijderd, maar dezelfde redenering):
 * de templates/single.html template zelf, of een los `post` (voor het geval
 * een editor het block toch ergens handmatig in een individueel bericht wil
 * plaatsen).
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/news-single-hero'];

    $post = $editor_context->post ?? null;
    $is_news_context = $post && (
        $post->post_type === 'post'
        || ($post->post_type === 'wp_template' && $post->post_name === 'single')
    );

    if ($is_news_context) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
