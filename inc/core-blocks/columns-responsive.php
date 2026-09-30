<?php
defined('ABSPATH') || exit();

/**
 * "Kolommen per rij" op tablet en mobiel voor core/columns. Core kent alleen
 * "Onder elkaar op mobiel" (isStackedOnMobile, één breakpoint op 782px): een
 * rij van 4 kan niet naar 2 op tablet. Deze twee attributes vullen dat gat:
 *
 * - ownColumnsTablet (600–1023px): 1–4 kolommen per rij, 0 = core-gedrag.
 * - ownColumnsMobile (< 600px):    2–3 kolommen per rij, 0 = core-gedrag
 *                                  (dus: volgt "Onder elkaar op mobiel").
 *
 * Desktop (≥ 1024px) blijft altijd core: het aantal kolom-blokken, met hun
 * eigen breedtes.
 *
 * Zelfde opzet als columns-reverse-mobile.php: attribute via
 * block_type_metadata_settings, classes via render_block_core/columns (niet
 * via blocks.getSaveElement — dan zou elke bestaande columns-block zijn
 * block-validatie verliezen zodra de opgeslagen markup verandert). Het
 * editor-paneel en de canvas-classes staan in columns-responsive.js; dat
 * paneel bevat óók de toggle voor ownReverseOnMobile, die tot nu toe geen UI
 * had.
 *
 * De layout zelf is CSS (src/css/components/columns.css, "Kolommen per rij"):
 * grid i.p.v. flex op die breakpoints, zodat de gap van de block-layout
 * vanzelf blijft kloppen zonder calc() op een onbekende gap-waarde.
 */

const OWNFRAMEWORK_COLUMNS_TABLET_OPTIONS = [1, 2, 3, 4];
const OWNFRAMEWORK_COLUMNS_MOBILE_OPTIONS = [2, 3];

add_filter('block_type_metadata_settings', function (array $settings, array $metadata): array {
    if (($metadata['name'] ?? '') !== 'core/columns') {
        return $settings;
    }

    $settings['attributes']['ownColumnsTablet'] = ['type' => 'integer', 'default' => 0];
    $settings['attributes']['ownColumnsMobile'] = ['type' => 'integer', 'default' => 0];

    return $settings;
}, 10, 2);

add_filter('render_block_core/columns', function (string $block_content, array $block): string {
    $tablet = (int) ($block['attrs']['ownColumnsTablet'] ?? 0);
    $mobile = (int) ($block['attrs']['ownColumnsMobile'] ?? 0);

    $classes = [];
    if (in_array($tablet, OWNFRAMEWORK_COLUMNS_TABLET_OPTIONS, true)) {
        $classes[] = 'own-cols-tablet-' . $tablet;
    }
    if (in_array($mobile, OWNFRAMEWORK_COLUMNS_MOBILE_OPTIONS, true)) {
        $classes[] = 'own-cols-mobile-' . $mobile;
    }

    if (!$classes || trim($block_content) === '') {
        return $block_content;
    }

    $processor = new WP_HTML_Tag_Processor($block_content);
    if (!$processor->next_tag()) {
        return $block_content;
    }

    foreach ($classes as $class) {
        $processor->add_class($class);
    }

    return $processor->get_updated_html();
}, 10, 2);

// Classic enqueue, not the Vite editor entry: that one loads as a deferred
// module, too late for blocks.registerBlockType (see button-arrow-icon.php).
add_action('enqueue_block_editor_assets', function (): void {
    $file = get_template_directory() . '/inc/core-blocks/columns-responsive.js';

    if (!file_exists($file)) {
        return;
    }

    wp_enqueue_script(
        'own-columns-responsive',
        get_template_directory_uri() . '/inc/core-blocks/columns-responsive.js',
        ['wp-blocks', 'wp-element', 'wp-hooks', 'wp-block-editor', 'wp-components'],
        filemtime($file),
        true
    );
});
