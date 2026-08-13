<?php
defined('ABSPATH') || exit();

/**
 * Content blokken slider — dezelfde opzet als acf/afbeeldingen-slider's
 * "Standaard"-variant (titel + ronde vorige/volgende-knoppen op één rij,
 * optionele paginatie-bolletjes eronder), maar met acf/content-blok-kaarten
 * als slides i.p.v. losse afbeeldingen.
 *
 * De slides zijn ECHTE geneste blocks (InnerBlocks, allowedBlocks beperkt tot
 * acf/content-blok), geen eigen herhaalveld: zo houdt elke kaart al zijn eigen
 * native controls (afbeelding, richting, achtergrondkleur, de eigen kop/tekst/
 * knoppen-InnerBlocks) zonder dat dit block daar iets van hoeft te dupliceren.
 * Zelfde patroon als acf/footer, dat acf/footer-cta/-menu/-contact op precies
 * die manier nest.
 *
 * EDITOR EN FRONT-END RENDEREN BEWUST VERSCHILLEND, en dat is de kern van dit
 * bestand:
 *
 * - Front-end: elke slide moet in zijn eigen <div class="swiper-slide">
 *   zitten, want dát is wat Swiper als slide herkent. SCF's eigen
 *   <InnerBlocks />-vervanging levert echter één blok HTML voor ALLE kinderen
 *   samen — er is geen haakje om per kind een wrapper omheen te zetten. Dus
 *   wordt hier niet die placeholder gebruikt, maar $wp_block->inner_blocks
 *   (de echte geparste block-tree, zelfde mechanisme als acf/header gebruikt
 *   om logo/CTA in het mobielmenu te herhalen): per kind ->render(), elk in
 *   zijn eigen .swiper-slide.
 * - Editor ($is_preview): juist WEL de gewone <InnerBlocks />-placeholder, in
 *   een simpel grid i.p.v. een Swiper. Alleen die placeholder maakt de kaarten
 *   klikbaar/bewerkbaar; $wp_block->inner_blocks levert statische HTML zonder
 *   editor-handvatten. Een Swiper eromheen zou daar bovendien tegenwerken —
 *   Swiper verbergt/transformeert alle niet-actieve slides, dus precies de
 *   kaarten die een editor wil aanklikken zijn dan niet bereikbaar. Een grid
 *   toont ze allemaal tegelijk, blijft volledig bewerkbaar, en laat nog steeds
 *   zien dát het losse kaarten naast elkaar zijn. Daarom staat dit block ook
 *   NIET in src/js/editor-scripts.js (anders dan acf/hero en acf/afbeeldingen-
 *   slider): er draait in de editor met opzet helemaal geen Swiper.
 *
 * "slidesPerView" geldt alleen vanaf lg (zie src/js/scripts/content-slider.js)
 * — daaronder staat het aantal vast (1.1 resp. 2), zodat een kaart op een
 * telefoon nooit tot een onleesbare kolombreedte geknepen wordt, ongeacht wat
 * hier gekozen is.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter of "!": wptexturize() zet een cijfer direct gevolgd
 * door een aanhalingsteken om in een dubbele-prime-entity, wat het sluitende
 * aanhalingsteken kapotmaakt. Class-lijsten hieronder zijn daarom zo geordend
 * dat ze altijd op een letter (of "!") eindigen, nooit op een cijfer.
 */

$title = trim($block['title'] ?? '');
$show_pagination = !empty($block['showPagination']);
$nav_color = $block['navColor'] ?: 'var(--wp--preset--color--primary)';
$pagination_color = $block['paginationColor'] ?: 'var(--wp--preset--color--primary)';

// Alleen 2/3/4 zijn geldige keuzes (editor.js); alles anders valt terug op de
// block.json-default i.p.v. een willekeurig getal door te geven aan Swiper.
$slides_per_view = (int) ($block['slidesPerView'] ?? 3);
if (!in_array($slides_per_view, [2, 3, 4], true)) {
    $slides_per_view = 3;
}

if (!empty($block['style']['color']['background'])) {
    $bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
    $bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
    $bg_value = '';
}

if (!empty($block['style']['color']['text'])) {
    $text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
    $text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
    $text_value = '';
}

// Geen background-color hier: die gaat naar het full-bleed laagje in de markup
// hieronder, zodat een gekozen achtergrondkleur tot de randen van het scherm
// loopt i.p.v. te stoppen bij de (align-)breedte van deze sectie. WordPress'
// eigen kleur-support zet 'm daarnaast alsnog automatisch op de sectie zelf
// (via get_block_wrapper_attributes(), buiten dit bestand om) — onschadelijk,
// dat valt volledig onder het bredere laagje.
$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
// Altijd als laatste, nooit conditioneel: garandeert dat de style-attribute
// nooit op een cijfer eindigt (zie de docblock hierboven).
$style_parts[] = 'box-sizing:border-box';

// get_block_wrapper_attributes() vertaalt de align-attribute bij een SCF-block
// met "mode": "preview" niet betrouwbaar naar een "alignwide"-klasse op de
// front-end — die dus zelf toevoegen, zelfde aanpak als acf/afbeeldingen-
// slider en acf/hero.
$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('own-content-slider relative ' . $align_class),
    'style' => implode(';', $style_parts),
]);

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg. Alleen in de editor gerenderd (zie de
 * docblock hierboven); de front-end bouwt zijn slides uit
 * $wp_block->inner_blocks.
 */
$slider_inner_blocks = '<InnerBlocks className=\'own-content-slider-editor\' template=\'[["acf/content-blok",{}],["acf/content-blok",{}],["acf/content-blok",{}]]\' allowedBlocks=\'["acf/content-blok"]\' templateLock=\'false\' />';

// ->render() per kind i.p.v. de InnerBlocks-placeholder, zodat elke kaart zijn
// eigen .swiper-slide krijgt (zie de docblock hierboven). Buiten de editor is
// dit de enige bron van slides; blijft $wp_block onverhoopt leeg, dan rendert
// de slider gewoon niets i.p.v. een fatale fout.
$slides = [];
if (!empty($wp_block) && $wp_block instanceof WP_Block) {
    foreach ($wp_block->inner_blocks as $inner_block) {
        $slides[] = $inner_block->render();
    }
}
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php // flex-col op mobiel: een lange titel plus de twee ronde knoppen op één
        // rij verdrukken elkaar op een smal scherm — vanaf "sm" is er ruimte genoeg.
        // Zelfde koprij als acf/afbeeldingen-slider.
        ?>
        <div class="gap-4 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <?php if ($title !== '') : ?>
                <h2 class="mt-0! mb-0!"><?= esc_html($title); ?></h2>
            <?php elseif (!empty($is_preview)) : ?>
                <h2 class="opacity-50 mt-0! mb-0!"><?php esc_html_e('Titel…', 'own-framework'); ?></h2>
            <?php endif; ?>
            <div class="gap-2 shrink-0 [&_.swiper-button-disabled]:opacity-50 flex items-center select-none">
                <div role="button" tabindex="0" class="own-content-slider-prev flex w-9 h-9 sm:w-10 sm:h-10 rounded-full cursor-pointer" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Vorige', 'own-framework'); ?>">
                    <svg class="w-4 h-4 m-auto text-white rotate-180 sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
                <div role="button" tabindex="0" class="own-content-slider-next flex w-9 h-9 sm:w-10 sm:h-10 rounded-full cursor-pointer" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Volgende', 'own-framework'); ?>">
                    <svg class="w-4 h-4 m-auto text-white sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
            </div>
        </div>

        <?php if (!empty($is_preview)) : ?>
            <?= $slider_inner_blocks; ?>
        <?php elseif (!empty($slides)) : ?>
            <div class="swiper overflow-visible! select-none" data-own-content-slider="<?= esc_attr((string) $slides_per_view); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($slides as $slide) : ?>
                        <div class="swiper-slide h-auto!"><?= $slide; ?></div>
                    <?php endforeach; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-content-slider-pagination mt-2 w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
