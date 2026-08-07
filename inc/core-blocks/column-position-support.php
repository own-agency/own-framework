<?php
defined('ABSPATH') || exit();

/**
 * Adds sticky "position" support to core/column, so one toggle keeps a whole
 * column visible while scrolling instead of a block inside it.
 *
 * theme.json already enables sticky globally (settings.position.sticky), but
 * core/column's own block.json doesn't declare the support and can't be edited
 * — block_type_metadata_settings is the hook for that.
 */
add_filter('block_type_metadata_settings', function (array $settings, array $metadata): array {
    if (($metadata['name'] ?? '') === 'core/column') {
        $settings['supports']['position'] = ['sticky' => true];
    }

    return $settings;
}, 10, 2);
