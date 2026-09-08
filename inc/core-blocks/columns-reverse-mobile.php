<?php
defined('ABSPATH') || exit();

/**
 * Adds an "ownReverseOnMobile" attribute to core/columns, so an editor can
 * flip a stacked column layout's visual order on mobile (e.g. an image
 * column that should appear ABOVE its text column once stacked, even though
 * it sits second in the desktop row) without reordering the actual blocks —
 * which would also reorder them on desktop, the one screen size where the
 * current order was presumably chosen on purpose.
 *
 * block_type_metadata_settings, same hook and same reason as this file's own
 * sibling column-position-support.php: core/columns' own block.json can't be
 * edited directly, and this is a genuine "supports"-shaped addition (a
 * layout behaviour toggle), not a content attribute — except an attribute is
 * literally what's needed here (a plain boolean the editor's own toolbar/
 * Inspector can read and write), so it's added to $settings['attributes']
 * directly rather than $settings['supports'].
 *
 * WordPress' own native block visibility feature (metadata.blockVisibility,
 * applied automatically to every block, this one included) is independent
 * of this attribute and doesn't need to share a hook with it.
 *
 * The actual flip is pure CSS (src/css/components/columns.css's own
 * ".own-columns-reverse-mobile" rule, "flex-direction: column-reverse" only
 * below core's own 782px stacking breakpoint) — reversing DOM order via PHP
 * would also reverse it for screen readers/no-CSS/print, which isn't the
 * intent (the content's real, authored order stays the desktop order; only
 * the STACKED visual presentation flips).
 */
add_filter('block_type_metadata_settings', function (array $settings, array $metadata): array {
    if (($metadata['name'] ?? '') !== 'core/columns') {
        return $settings;
    }

    if (!isset($settings['attributes']) || !is_array($settings['attributes'])) {
        $settings['attributes'] = [];
    }

    $settings['attributes']['ownReverseOnMobile'] = [
        'type' => 'boolean',
        'default' => false,
    ];

    return $settings;
}, 10, 2);

/**
 * render_block_core/columns runs only for this one block type — narrower and
 * clearer than filtering the generic "render_block" a second time (inc/
 * functions/block-visibility.php's own filter already covers the hide-on-
 * mobile/desktop classes for every block, this one only ever needs to add
 * ITS OWN class to core/columns specifically).
 */
add_filter('render_block_core/columns', function (string $block_content, array $block): string {
    if (empty($block['attrs']['ownReverseOnMobile'])) {
        return $block_content;
    }

    if (trim($block_content) === '') {
        return $block_content;
    }

    $processor = new WP_HTML_Tag_Processor($block_content);
    if (!$processor->next_tag()) {
        return $block_content;
    }

    $processor->add_class('own-columns-reverse-mobile');

    return $processor->get_updated_html();
}, 10, 2);
