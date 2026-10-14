<?php
defined("ABSPATH") || exit();

/**
 * Extra core/image style variant "Geen afronding": opts a single image out of
 * the site-wide "Hoekafronding" scale that src/css/components/core-images.css
 * otherwise applies to every core/image. A block style rather than a toggle
 * attribute because core/image already carries a Styles panel (WP's own
 * "Standaard"/"Afgerond"), and "square" is just a third, mutually exclusive
 * look alongside those two — same mechanism as button-styles.php. The CSS
 * side is a `:not(.is-style-square)` exclusion in core-images.css, not a
 * "border-radius: 0" override, so an editor's own manual radius (native
 * Border panel, inline style) still applies on top if they set one.
 */
add_action('init', function (): void {
    register_block_style('core/image', [
        'name' => 'square',
        'label' => __('Geen afronding', 'own-framework'),
    ]);
});
