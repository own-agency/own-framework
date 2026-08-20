<?php
defined('ABSPATH') || exit();

/**
 * acf/vacature-share leest get_permalink()/get_the_title() van "de huidige
 * vacature" — buiten een vacature-context is dat geen zinnige plek. Zelfde
 * patroon als blocks/vacature-apply/hooks/restrict-to-vacancy-context.php:
 * templates/single-vacancy.html zelf, of een losse vacature-post.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/vacature-share'];

    $post = $editor_context->post ?? null;
    $is_vacancy_context = $post && (
        $post->post_type === 'vacancy'
        || ($post->post_type === 'wp_template' && $post->post_name === 'single-vacancy')
    );

    if ($is_vacancy_context) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
