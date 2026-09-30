<?php
defined('ABSPATH') || exit();

/**
 * Footer-kolommen stapelen altijd op mobiel, ongeacht de toggle "Stapel op
 * mobiel" in de opgeslagen footer. Drie footerkolommen naast elkaar op een
 * telefoon is nooit bedoeld, en een footer die ooit met de toggle uit is
 * opgeslagen (of waarvan de editor hem uit toont, zie render.php's
 * InnerBlocks-template) blijft anders zo staan: het templatebestand
 * (parts/footer.html) wordt niet meer gebruikt zodra de footer in de
 * database staat.
 *
 * Twee stappen, omdat core/columns een STATIC block is: render_block_data
 * kent de ouder (acf/footer), maar de class "is-not-stacked-on-mobile" zit
 * al in de opgeslagen HTML en verdwijnt niet door alleen het attribute om
 * te zetten. De marker ("ownForceStacked") geeft de ouder-informatie door
 * aan render_block_core/columns, dat de class daadwerkelijk verwijdert.
 * Alleen directe kinderen van acf/footer; kolommen elders op de site
 * houden gewoon hun eigen instelling.
 */
add_filter('render_block_data', function (array $parsed_block, array $source_block, $parent_block): array {
    if (($parsed_block['blockName'] ?? '') !== 'core/columns') {
        return $parsed_block;
    }

    if (!$parent_block instanceof WP_Block || $parent_block->name !== 'acf/footer') {
        return $parsed_block;
    }

    $parsed_block['attrs']['isStackedOnMobile'] = true;
    $parsed_block['attrs']['ownForceStacked'] = true;

    return $parsed_block;
}, 10, 3);

add_filter('render_block_core/columns', function (string $block_content, array $block): string {
    if (empty($block['attrs']['ownForceStacked'])) {
        return $block_content;
    }

    $processor = new WP_HTML_Tag_Processor($block_content);
    if (!$processor->next_tag()) {
        return $block_content;
    }

    $processor->remove_class('is-not-stacked-on-mobile');

    return $processor->get_updated_html();
}, 10, 2);
