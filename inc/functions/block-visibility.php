<?php
defined('ABSPATH') || exit();

/**
 * "Verbergen op mobiel"/"Verbergen op desktop" — one settings panel added to
 * EVERY block (this theme's own blocks and every native core block alike),
 * so an editor can build two different blocks (e.g. a simplified mobile hero
 * next to a richer desktop one) and toggle which one shows where, without a
 * developer adding a bespoke attribute per block. Same centralised-filter
 * approach as inc/functions/default-block-align.php's own "wide" default —
 * one `register_block_type_args` filter reaches every block type instead of
 * editing 30+ individual block.json files, and unlike that file this one
 * never needs to touch each block's own render.php either: the visibility
 * classes are injected generically via `render_block`, after the block has
 * already rendered its own markup, so no block needs to know this feature
 * exists at all.
 *
 * Breakpoint: 1024px (Tailwind's "lg"), the same mobile/desktop split this
 * theme already uses everywhere else (acf/header's own "lg:hidden"/
 * "lg:block" mobile-menu toggle) — not core's own 782px columns-stacking
 * breakpoint, which is about layout density, not "is this a phone or a
 * desktop", a different question.
 *
 * See src/css/components/block-visibility.css for the two classes this
 * injects, and inc/functions/block-visibility-editor.js for the matching
 * Inspector UI (a hand-written script, no Vite build step — same pattern as
 * every block's own editor.js, e.g. blocks/hero/editor.js's own docblock).
 */

add_filter('register_block_type_args', function (array $args): array {
    if (!isset($args['attributes']) || !is_array($args['attributes'])) {
        $args['attributes'] = [];
    }

    // Only added if genuinely absent — unlike default-block-align.php's own
    // "align", no block in this theme (or core) already declares an
    // attribute under these exact names, so there is nothing to
    // unconditionally overwrite here.
    if (!isset($args['attributes']['ownHideOnMobile'])) {
        $args['attributes']['ownHideOnMobile'] = [
            'type' => 'boolean',
            'default' => false,
        ];
    }
    if (!isset($args['attributes']['ownHideOnDesktop'])) {
        $args['attributes']['ownHideOnDesktop'] = [
            'type' => 'boolean',
            'default' => false,
        ];
    }

    return $args;
});

/**
 * Injects the two classes into whatever the block actually rendered, once,
 * after every other filter/render_callback has already run — deliberately
 * NOT something each block's own render.php has to remember to apply itself
 * (unlike e.g. "align", which every block reads and acts on individually,
 * this is generic enough to bolt on from the outside for every block at
 * once). WP_HTML_Tag_Processor (core since WP 6.2) rather than a regex: it
 * already knows how to safely add a class to an existing class="" attribute,
 * or create one if the tag has none, without accidentally matching a class=
 * string that appears inside an attribute VALUE instead of as a real
 * attribute (e.g. an inline SVG's own href, or a data-* attribute holding
 * JSON — both appear elsewhere in this theme's own block output).
 *
 * next_tag() finds only the block's FIRST tag — correct for every block in
 * this theme (each has exactly one root element, via
 * get_block_wrapper_attributes() or a native block's own single wrapper),
 * but a block whose save()/render output is several sibling top-level tags
 * would only have its first one hidden. No such block exists here today.
 */
add_filter('render_block', function (string $block_content, array $block): string {
    $hide_mobile = !empty($block['attrs']['ownHideOnMobile']);
    $hide_desktop = !empty($block['attrs']['ownHideOnDesktop']);

    if (!$hide_mobile && !$hide_desktop) {
        return $block_content;
    }

    if (trim($block_content) === '') {
        return $block_content;
    }

    $processor = new WP_HTML_Tag_Processor($block_content);
    if (!$processor->next_tag()) {
        return $block_content;
    }

    if ($hide_mobile) {
        $processor->add_class('own-hide-mobile');
    }
    if ($hide_desktop) {
        $processor->add_class('own-hide-desktop');
    }

    return $processor->get_updated_html();
}, 10, 2);

/**
 * Hand-written (no wp-scripts build step for this theme, same as every
 * block's own editor.js) — declares its own dependencies explicitly since,
 * unlike a block.json "editorScript" entry, there is no matching
 * "*.asset.php" for WordPress to read automatically for a script registered
 * by hand like this.
 */
add_action('enqueue_block_editor_assets', function (): void {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    $file = '/inc/functions/block-visibility-editor.js';

    wp_enqueue_script(
        'own-block-visibility-editor',
        $theme_uri . $file,
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-hooks'],
        (string) filemtime($theme_dir . $file),
        true,
    );
});
