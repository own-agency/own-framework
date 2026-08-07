<?php
defined('ABSPATH') || exit;

// Hide core's bundled patterns and the ones fetched from the wordpress.org
// directory, so the inserter only shows this theme's own. Template parts and
// user-created patterns in the database are unaffected.
add_action('after_setup_theme', function (): void {
    remove_theme_support('core-block-patterns');
});

// Category for this theme's own patterns (/patterns), mirroring the
// "own-framework" block category registered in functions.php.
add_action('init', function (): void {
    register_block_pattern_category('own-framework', [
        'label' => __('OWN Framework', 'own-framework'),
    ]);
});
