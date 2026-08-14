<?php
defined('ABSPATH') || exit();

/**
 * acf/vacature-single-hero hoort nergens anders dan templates/single-vacancy.
 * html — het leest get_the_title()/get_the_excerpt()/de vacature-taxonomieën
 * van "de huidige vacature", wat buiten die template geen zinnige context is.
 * Zelfde patroon als blocks/vacature-hero/hooks/restrict-to-archive-vacancy-
 * template.php, alleen voor de andere template.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/vacature-single-hero'];

    $post = $editor_context->post ?? null;
    $is_single_vacancy_template = $post
        && $post->post_type === 'wp_template'
        && $post->post_name === 'single-vacancy';

    if ($is_single_vacancy_template) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
