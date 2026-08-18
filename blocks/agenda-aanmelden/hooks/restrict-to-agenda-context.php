<?php
defined('ABSPATH') || exit();

/**
 * acf/agenda-aanmelden leest per-post velden van "het huidige moment"
 * (ownframework_agenda_get_signup(), via get_the_ID()) — buiten een
 * agenda-context is dat geen zinnige plek. Twee plekken zijn geldig, zelfde
 * patroon als blocks/vacature-apply/hooks/restrict-to-vacancy-context.php:
 * de templates/single-agenda.html template zelf, of een losse
 * agenda-POST (voor het geval een editor het block toch ergens in een
 * individueel moment wil plaatsen).
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/agenda-aanmelden'];

    $post = $editor_context->post ?? null;
    $is_agenda_context = $post && (
        $post->post_type === 'agenda'
        || ($post->post_type === 'wp_template' && $post->post_name === 'single-agenda')
    );

    if ($is_agenda_context) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
