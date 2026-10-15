<?php
defined('ABSPATH') || exit();

// "Afgeronde hoeken" op core/table — zie table-corners.js.
add_action('enqueue_block_editor_assets', function (): void {
    $file = get_template_directory() . '/inc/core-blocks/table-corners.js';

    if (!file_exists($file)) {
        return;
    }

    wp_enqueue_script(
        'own-table-corners',
        get_template_directory_uri() . '/inc/core-blocks/table-corners.js',
        ['wp-blocks', 'wp-element', 'wp-hooks', 'wp-block-editor', 'wp-components'],
        filemtime($file),
        true
    );
});
