<?php
defined('ABSPATH') || exit;

/* `Multisite pattern sync
 ----------------------------------------------------------------------------------------------------*/

// Pushes user-created patterns (the wp_block CPT, synced and unsynced) from
// the main site to every other site in the network on create/update. Based on
// https://rudrastyh.com/wordpress-multisite/sync-patterns-and-template-parts-across-sites.html
// with three deliberate differences:
//
// 1. One-way, main site only. Subsites are separate clients; a pattern made
//    on one of them must never show up on the others.
// 2. Copies are matched by a source reference in post meta, not by title, so
//    renaming a pattern on the main site updates the copies instead of
//    creating duplicates. Title is only the fallback for copies that predate
//    this file.
// 3. A re-entrancy guard: inserting the copy fires publish_wp_block on the
//    subsite too.
//
// Known limits: block markup is copied verbatim, so attachment IDs (images)
// and nested pattern refs ({"ref":123}) still point at main-site IDs.
// Image URLs keep working; the ID-based editor features don't. Deleting a
// pattern on the main site leaves the copies in place.

const OWNFRAMEWORK_PATTERN_SYNC_META = '_ownframework_synced_from';

add_action('publish_wp_block', 'ownframework_sync_pattern_to_network', 10, 2);

function ownframework_sync_pattern_to_network(int $post_id, WP_Post $post): void
{
    static $syncing = false;

    if ($syncing || !is_multisite() || !is_main_site()) {
        return;
    }

    $syncing = true;

    $source_ref = get_current_blog_id() . ':' . $post_id;
    $sync_status = get_post_meta($post_id, 'wp_pattern_sync_status', true);
    $categories = wp_get_object_terms($post_id, 'wp_pattern_category', ['fields' => 'names']);
    if (is_wp_error($categories)) {
        $categories = [];
    }

    $site_ids = get_sites([
        'fields' => 'ids',
        'number' => 0,
        'site__not_in' => [get_current_blog_id()],
        'archived' => 0,
        'deleted' => 0,
        'spam' => 0,
    ]);

    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);

        $postarr = [
            'post_type' => 'wp_block',
            'post_status' => 'publish',
            'post_title' => $post->post_title,
            'post_name' => $post->post_name,
            // wp_insert_post() unslashes its input; block attribute JSON
            // contains backslashes that would otherwise be stripped.
            'post_content' => wp_slash($post->post_content),
        ];

        $existing_id = ownframework_find_synced_pattern($source_ref, $post->post_title);
        if ($existing_id) {
            $postarr['ID'] = $existing_id;
            $target_id = wp_update_post($postarr, true);
        } else {
            $target_id = wp_insert_post($postarr, true);
        }

        if (!is_wp_error($target_id) && $target_id) {
            update_post_meta($target_id, OWNFRAMEWORK_PATTERN_SYNC_META, $source_ref);

            // Core only stores this meta for unsynced patterns; synced ones
            // have none at all.
            if ($sync_status) {
                update_post_meta($target_id, 'wp_pattern_sync_status', $sync_status);
            } else {
                delete_post_meta($target_id, 'wp_pattern_sync_status');
            }

            // Names, not IDs: term IDs differ per site, and missing terms are
            // created on the fly.
            wp_set_object_terms($target_id, $categories, 'wp_pattern_category');
        }

        restore_current_blog();
    }

    $syncing = false;
}

// Runs inside switch_to_blog(), so queries the subsite's own tables.
function ownframework_find_synced_pattern(string $source_ref, string $title): int
{
    $by_ref = get_posts([
        'post_type' => 'wp_block',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => OWNFRAMEWORK_PATTERN_SYNC_META,
        'meta_value' => $source_ref,
        'no_found_rows' => true,
    ]);
    if ($by_ref) {
        return (int) $by_ref[0];
    }

    // Fallback for copies made before the source reference existed. Only
    // adopt a pattern that isn't already linked to another source.
    $by_title = get_posts([
        'post_type' => 'wp_block',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'title' => $title,
        'meta_query' => [['key' => OWNFRAMEWORK_PATTERN_SYNC_META, 'compare' => 'NOT EXISTS']],
        'no_found_rows' => true,
    ]);

    return $by_title ? (int) $by_title[0] : 0;
}
