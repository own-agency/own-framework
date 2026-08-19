<?php
defined('ABSPATH') || exit();

/**
 * acf/news-share leest get_permalink()/get_the_title() van "het huidige
 * bericht" — buiten een echte `post`-context is dat geen zinnige plek. Zelfde
 * patroon als blocks/news-single-hero/hooks/restrict-to-single-post.php: de
 * templates/single.html template zelf, of een los `post`.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/news-share'];

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
