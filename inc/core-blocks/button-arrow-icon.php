<?php
defined('ABSPATH') || exit();

/**
 * "Toon pijl-icoon" — een optionele attribute op het NATIVE core/button
 * (niet een eigen block, zie inc/core-blocks/button-styles.php's docblock
 * voor waarom "core-blocks" de plek is voor dit soort uitbreidingen op een
 * bestaand block i.p.v. een eigen acf/*-block).
 *
 * core/button is een STATIC block — de markup wordt door de editor zelf in
 * post_content opgeslagen (save(), geen render.php/PHP render-callback) — dus
 * dit hoeft alleen client-side te draaien: button-arrow-icon.js voegt de
 * attribute, het "Toon pijl-icoon"-paneel én de marker-class toe (zowel in de
 * canvas als in de opgeslagen output). Het icoon zelf is puur CSS (src/css/
 * components/button-arrow-icon.css, ::after met mask-image) — zie dat
 * bestand's eigen docblock voor waarom, en button-arrow-icon.js's eigen
 * docblock voor waarom de marker-class-aanpak i.p.v. een echt geïnjecteerd
 * SVG-element.
 *
 * Los, klein en direct enqueued (geen Vite-build, geen "editorScript" op een
 * eigen block.json — core/button hééft er geen om aan te haken) i.p.v. via
 * theme-scripts-editor (src/js/editor-scripts.js): dat bestand wordt door
 * enqueue-scripts-styles.php's eigen script_loader_tag-filter geforceerd als
 * `type="module"` geladen (voor zijn eigen gedeelde-chunk-import), en module-
 * scripts laden altijd UITGESTELD t.o.v. classic scripts (CLAUDE.md Traps
 * #26) — voor "blocks.registerBlockType" moet dit bestand al gedraaid hebben
 * VOORDAT wp-block-library core/button registreert, dus een gewone classic
 * enqueue met "wp-blocks"/"wp-hooks" als afhankelijkheid (WordPress' eigen
 * dependency-volgorde, geen race) is hier de betrouwbare keuze, niet een
 * inline-script-op-"wp-hooks"-truc zoals ownframework_relabel_align_controls()
 * — dat was alleen nodig omdat theme-scripts-editor specifiek zelf al
 * (gedwongen) een module is; een verse, aparte handle heeft dat probleem niet.
 */
add_action('enqueue_block_editor_assets', function (): void {
    $file = get_template_directory() . '/inc/core-blocks/button-arrow-icon.js';

    if (!file_exists($file)) {
        return;
    }

    wp_enqueue_script(
        'own-button-arrow-icon',
        get_template_directory_uri() . '/inc/core-blocks/button-arrow-icon.js',
        ['wp-blocks', 'wp-element', 'wp-hooks', 'wp-block-editor', 'wp-components'],
        filemtime($file),
        true
    );
});
