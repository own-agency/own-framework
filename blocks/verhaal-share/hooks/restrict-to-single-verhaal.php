<?php
defined('ABSPATH') || exit();

/**
 * acf/verhaal-share leest get_permalink()/get_the_title() van "het huidige
 * verhaal" — buiten templates/single-verhaal.html is dat geen zinnige plek.
 * Zelfde patroon als blocks/verhaal-single-hero/hooks/
 * restrict-to-single-verhaal-template.php.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/verhaal-share'];

    $post = $editor_context->post ?? null;
    $is_single_verhaal_template = $post
        && $post->post_type === 'wp_template'
        && $post->post_name === 'single-verhaal';

    if ($is_single_verhaal_template) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
