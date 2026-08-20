<?php
defined('ABSPATH') || exit();

/**
 * acf/verhaal-hero en acf/verhalen horen nergens anders te bestaan dan in
 * templates/archive-verhaal.html — acf/verhalen's eigen WP_Query/paginering
 * (blocks/verhalen/hooks/query.php) verwacht de verhalen-archiefcontext, niet
 * een willekeurige pagina. Verbergt ze daarom uit de inserter overal BEHALVE
 * als je die ene template zelf bewerkt — zelfde patroon als blocks/
 * agenda-hero/hooks/restrict-to-archive-agenda-template.php (dat ook beide
 * "eigen" archiefblocks in één hook regelt, vanuit de hero's eigen folder).
 *
 * post_name (niet een ID): zolang niemand de template ooit aanpast heeft
 * templates/archive-verhaal.html geen eigen rij in de database — WordPress
 * synthetiseert dan een virtuele wp_template-post met post_name afgeleid van
 * de bestandsnaam. Zodra een editor 'm wel aanpast, krijgt de ECHTE,
 * opgeslagen versie dezelfde slug.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/verhaal-hero', 'acf/verhalen'];

    $post = $editor_context->post ?? null;
    $is_archive_verhaal_template = $post
        && $post->post_type === 'wp_template'
        && $post->post_name === 'archive-verhaal';

    if ($is_archive_verhaal_template) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
