<?php
defined('ABSPATH') || exit();

/**
 * Adds an "ownMobileWidth" attribute (integer, px) to core/image and
 * core/site-logo, so an editor can give an image/logo a different width on
 * mobile than the native "Breedte" they set for desktop — e.g. a 150px logo
 * that should be 110px once the screen is narrow. WordPress' own width
 * setting on both blocks is a single value with no per-breakpoint variant.
 *
 * Same three-part setup as columns-reverse-mobile.php: attribute via
 * block_type_metadata_settings, front-end class + custom property via
 * render_block_<name> (so the saved markup is untouched and existing blocks
 * keep validating), and the editor control/canvas preview in
 * mobile-width.js. The actual override is pure CSS
 * (src/css/components/mobile-width.css), below core's own 782px "mobile"
 * breakpoint — the same one "Stapel op mobiel"/"Omdraaien op mobiel" use.
 */
const OWN_MOBILE_WIDTH_BLOCKS = ['core/image', 'core/site-logo'];

add_filter('block_type_metadata_settings', function (array $settings, array $metadata): array {
    if (!in_array($metadata['name'] ?? '', OWN_MOBILE_WIDTH_BLOCKS, true)) {
        return $settings;
    }

    if (!isset($settings['attributes']) || !is_array($settings['attributes'])) {
        $settings['attributes'] = [];
    }

    $settings['attributes']['ownMobileWidth'] = [
        'type' => 'integer',
    ];

    return $settings;
}, 10, 2);

/**
 * Puts the width on the block's outer wrapper as --own-mobile-width (plus a
 * marker class), not on the <img> itself: core/image's own inline
 * "width:150px" sits on the <img>, and the CSS needs to win against exactly
 * that, which it does from a media query with !important — an inline style
 * we'd add next to it couldn't be scoped to mobile at all.
 */
$own_mobile_width_render = function (string $block_content, array $block): string {
    $width = absint($block['attrs']['ownMobileWidth'] ?? 0);

    if ($width === 0 || trim($block_content) === '') {
        return $block_content;
    }

    $processor = new WP_HTML_Tag_Processor($block_content);
    if (!$processor->next_tag()) {
        return $block_content;
    }

    $processor->add_class('own-has-mobile-width');

    $style = trim((string) $processor->get_attribute('style'));
    if ($style !== '' && !str_ends_with($style, ';')) {
        $style .= ';';
    }
    $processor->set_attribute('style', $style . '--own-mobile-width:' . $width . 'px;');

    return $processor->get_updated_html();
};

foreach (OWN_MOBILE_WIDTH_BLOCKS as $own_mobile_width_block) {
    add_filter('render_block_' . $own_mobile_width_block, $own_mobile_width_render, 10, 2);
}

// Editor control + canvas preview (mobile-width.js). Classic enqueue, not the
// Vite editor entry: that one loads as a deferred module, too late for
// blocks.registerBlockType (see button-arrow-icon.php).
add_action('enqueue_block_editor_assets', function (): void {
    $file = get_template_directory() . '/inc/core-blocks/mobile-width.js';

    if (!file_exists($file)) {
        return;
    }

    wp_enqueue_script(
        'own-mobile-width',
        get_template_directory_uri() . '/inc/core-blocks/mobile-width.js',
        ['wp-blocks', 'wp-element', 'wp-hooks', 'wp-block-editor', 'wp-components'],
        filemtime($file),
        true
    );
});
