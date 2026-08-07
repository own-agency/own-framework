<?php
defined("ABSPATH") || exit();

/**
 * Extra core/button style variants, alongside core's own built-in "Fill" and
 * "Outline" (both primary-coloured by editor choice) — "Tekstlink" covers
 * the 3rd CTA look editors expect (see blocks/header/render.php), and
 * "Secundair"/"Secundair (rand)" give a one-click secondary-coloured fill/
 * outline pair instead of an editor having to pick the secondary colour by
 * hand on a plain Fill/Outline button every time. CSS in src/css/components/
 * header.css.
 */
add_action('init', function (): void {
    register_block_style('core/button', [
        'name' => 'link',
        'label' => __('Tekstlink', 'own-framework'),
    ]);

    register_block_style('core/button', [
        'name' => 'secondary',
        'label' => __('Secundair', 'own-framework'),
    ]);

    register_block_style('core/button', [
        'name' => 'secondary-outline',
        'label' => __('Secundair (rand)', 'own-framework'),
    ]);
});
