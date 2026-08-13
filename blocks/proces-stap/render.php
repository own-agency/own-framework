<?php
defined('ABSPATH') || exit();

/**
 * Proces stap — één stap binnen acf/proces (block.json's "ancestor" houdt dit
 * block overal buiten de inserter behalve binnen zijn ouder, zelfde patroon
 * als acf/footer-cta/-menu/-contact binnen acf/footer).
 *
 * Nummer, vinkje (laatste stap) en verbindingslijn krijgen HIER geen enkele
 * PHP-logica: dit block weet niet, en hoeft niet te weten, hoeveel stappen er
 * zijn of op welke plek het zelf staat. Dat is precies wat CSS al gratis
 * geeft aan elk kind van acf/proces's ".own-proces-list" (src/css/components/
 * proces.css): een CSS counter nummert elke ".own-proces-stap" vanzelf, en
 * ":last-child" weet vanzelf welke stap de laatste is (wisselt het cijfer om
 * naar het vinkje hieronder, en verbergt zijn eigen verbindingslijn). Geen
 * cross-block data nodig zoals bij een SCF/Gutenberg block-context-mechanisme
 * — puur DOM-structuur plus CSS.
 *
 * "stepColor"/"stepNumberColor"/"stepLineColor" komen NIET van eigen
 * attributes hier, maar van CSS custom properties die acf/proces/render.php
 * op de sectie-wrapper zet (--own-proces-step-color e.d.) — gewone
 * CSS-overerving door de DOM heen, zie dat bestand's eigen docblock. Dit
 * block leest ze dus nergens uit PHP, alleen via var() in proces.css.
 *
 * De inhoud zelf (kop, tekst) is InnerBlocks, geen veld — zie acf/hero's
 * eigen docblock voor de volle uitleg van SCF's <InnerBlocks />-vervang-truc.
 * Geen "core/image"/"core/buttons" in allowedBlocks: een stap in een
 * aanvraagproces is uitleg, geen kaart met eigen media/CTA (dat is precies
 * wat acf/content-blok al is, en anders dan dat block heeft dit er ook geen
 * eigen achtergrondkleur-paneel voor nodig).
 */

$content_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":3,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Beschrijving…"}]]\' allowedBlocks=\'["core/heading","core/paragraph","core/list"]\' templateLock=\'false\' />';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'own-proces-stap flex flex-col',
]);
?>
<div <?= $wrapper_attributes; ?>>
    <div class="flex items-center gap-3 mb-6 w-full">
        <div class="own-proces-stap-number flex items-center justify-center w-10 h-10 text-sm shrink-0 rounded-full" aria-hidden="true">
            <span class="own-proces-stap-number-text"></span>
            <svg class="w-5 h-5 own-proces-stap-check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                <path d="M7 13L10 16L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <span class="own-proces-stap-connector flex-1 border-l-4 md:border-l-0 md:border-t-2 opacity-50 border-dashed"></span>
    </div>
    <div class="prose max-w-none">
        <?= $content_inner_blocks; ?>
    </div>
</div>
