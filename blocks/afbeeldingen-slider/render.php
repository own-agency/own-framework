<?php
defined('ABSPATH') || exit();

/**
 * Afbeeldingen slider — meerdere afbeeldingen naast elkaar via Swiper, geen
 * loop (zie src/js/scripts/image-slider.js). data-own-slider + .closest(
 * '.own-image-slider') scopet de prev/next-knoppen op de eigen sectie, blijft
 * correct met willekeurig veel sliders op één pagina zonder een uniek id/
 * klasse per instance te hoeven genereren.
 *
 * Elke <img> leest url/alt live via het attachment-ID i.p.v. de opgeslagen
 * waarde uit de block-attributes te vertrouwen — blijft correct als een
 * editor de bijschriftmetadata van een afbeelding later aanpast.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter of "!": wptexturize() (draait op alle blockoutput via
 * the_content) zet een cijfer direct gevolgd door een aanhalingsteken om in
 * een dubbele-prime-entity (bv. "opacity-50"" → "opacity-50&#8243;"), wat het
 * sluitende aanhalingsteken van de HTML-attribute kapotmaakt en de rest van
 * de markup laat wegdraaien. Class-lijsten hieronder zijn daarom zo
 * geordend dat ze altijd op een letter (of "!") eindigen, nooit op een cijfer.
 */

$title = trim($block['title'] ?? '');
$images = $block['images'] ?? [];
$slider_version = ($block['sliderVersion'] ?? 'default') === 'thumbs' ? 'thumbs' : 'default';
$nav_color = $block['navColor'] ?: 'var(--wp--preset--color--primary)';
// De miniaturenstrook (sliderVersion "thumbs") vervangt de rol van de
// paginatie-bolletjes (die tonen nu al welke afbeelding actief is) — een
// eerder ingeschakelde toggle van vóór het wisselen naar "thumbs" negeren
// i.p.v. allebei tegelijk te tonen.
$show_pagination = !empty($block['showPagination']) && $slider_version !== 'thumbs';
$pagination_color = $block['paginationColor'] ?: 'var(--wp--preset--color--primary)';

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
// nooit op een cijfer eindigt (een gekozen tekstkleur kan zelf een hexwaarde
// zijn, bv. "#062b20") — zelfde reden als de class-volgorde in de docblock.
$style_parts[] = 'box-sizing:border-box';

// Geen eigen py-* meer: dit block's "spacing.margin": true (block.json) geeft
// de editor al native controle over de ruimte boven/onder een sectie, dus een
// vaste interne padding daarnaast stapelde alleen maar op (dubbele, te grote
// ruimte). Verticale ruimte is nu volledig een keuze van de editor per
// instance i.p.v. hardcoded in dit block.

// get_block_wrapper_attributes() zou de align-attribute normaal automatisch
// naar een "alignfull"-klasse vertalen, maar bij een SCF-block met "mode":
// "preview" is dat op de front-end niet betrouwbaar (de editor toont de
// juiste breedte via Gutenberg's eigen block-wrapper, los van wat render.php
// teruggeeft) — de klasse dus zelf toevoegen i.p.v. erop vertrouwen dat de
// support hem al meelevert. Zonder expliciete keuze (oude content zonder
// "align" in de opgeslagen attributes) valt dit terug op "" (geen uitlijning,
// gebruikt de door de editor ingestelde inhoudsbreedte): dezelfde default als
// block.json's eigen "align"-attribute.
$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('own-image-slider relative overflow-hidden overflow-visible ' . $align_class),
    'style' => implode(';', $style_parts),
]);

/**
 * Haalt de huidige url/alt van een afbeelding op via het attachment-ID.
 * Valt terug op de opgeslagen waarde uit de block-attributes voor een
 * afbeelding zonder (geldig) ID.
 */
$get_image = function (array $image): array {
    $id = (int) ($image['id'] ?? 0);
    return [
        'url' => $id ? (wp_get_attachment_image_url($id, 'full') ?: '') : ($image['url'] ?? ''),
        'alt' => $id ? trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)) : ($image['alt'] ?? ''),
    ];
};
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php // flex-col op mobiel (i.p.v. altijd flex-row): een lange titel plus de
        // twee ronde knoppen op één rij verdrukken elkaar op een smal scherm anders
        // onnodig — vanaf "sm" is er ruimte genoeg voor naast elkaar. 
        ?>
        <div class="gap-4 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <?php if ($title !== '') : ?>
                <h2 class="mt-0! mb-0!"><?= esc_html($title); ?></h2>
            <?php elseif (!empty($is_preview)) : ?>
                <h2 class="opacity-50 mt-0! mb-0!"><?php esc_html_e('Titel…', 'own-framework'); ?></h2>
            <?php endif; ?>
            <div class="gap-2 shrink-0 [&_.swiper-button-disabled]:opacity-50 flex items-center select-none">
                <div role="button" tabindex="0" class="own-image-slider-prev flex w-9 h-9 sm:w-10 sm:h-10 rounded-full cursor-pointer" style="background-color:<?= esc_attr($nav_color); ?>" aria-label="<?php esc_attr_e('Vorige', 'own-framework'); ?>">
                    <svg class="w-4 h-4 m-auto text-white rotate-180 sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
                <div role="button" tabindex="0" class="own-image-slider-next flex w-9 h-9 sm:w-10 sm:h-10 rounded-full cursor-pointer" style="background-color:<?= esc_attr($nav_color); ?>" aria-label="<?php esc_attr_e('Volgende', 'own-framework'); ?>">
                    <svg class="w-4 h-4 m-auto text-white sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
            </div>
        </div>

        <?php if (empty($images)) : ?>
            <p class="mb-0 text-sm text-center"><?php esc_html_e('Er zijn geen afbeeldingen geselecteerd.', 'own-framework'); ?></p>
        <?php elseif ($slider_version === 'thumbs') : ?>
            <?php // data-own-slider="thumbs" (i.p.v. het kale data-own-slider hieronder)
            // vertelt image-slider.js dat dit de hoofdslider van een thumbs-paar is:
            // die initialiseert dan eerst .own-image-slider-thumbs als eigen Swiper-
            // instance, en geeft die pas daarna door aan deze hoofdslider's eigen
            // "thumbs"-optie — Swiper's Thumbs-module kan alleen een al bestaande
            // instance koppelen, niet een los stuk markup.
            //
            // Hoofdslider vóór de miniaturenstrook in de DOM: bij een gewone flex-
            // rij (geen "order"-override nodig) staat de strook daardoor al rechts,
            // zonder de hoofdslider expliciet naar links te moeten duwen. Geen
            // "overflow-visible!" hier (i.p.v. de standaardversie hieronder) — deze
            // versie moet juist wél binnen zijn eigen kolom blijven, geen afbeelding
            // die de rand van de container overschrijdt.
            //
            // "aspect-3/2" op de RIJ zelf (i.p.v. op elke afbeelding apart, zoals de
            // standaardversie hieronder doet): de rij krijgt zo een concrete hoogte
            // puur uit haar eigen breedte, zonder afhankelijk te zijn van "items-
            // stretch" die de hoogte via de kolommen' eigen inhoud moet afleiden.
            // Dat laatste bleek in de praktijk niet betrouwbaar zodra Swiper de
            // miniaturenstrook op "direction: vertical" zet — Swiper's eigen
            // hoogteberekening liep dan vast op een onduidelijke/ambiguë hoogte en
            // zette een absurd grote inline hoogte op de slides (de browser's eigen
            // maximale layoutgrootte, 2^24px). "h-full" op beide .swiper-containers
            // hieronder is nu een simpele, ondubbelzinnige overerving van díe ene,
            // al vaststaande rij-hoogte — geen aparte berekening per kolom meer.
            ?>
            <div class="flex gap-4 aspect-video">
                <div class="own-image-slider-main swiper select-none flex-1 min-w-0 h-full" data-own-slider="thumbs">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $image) :
                            $resolved = $get_image($image); ?>
                            <div class="swiper-slide">
                                <img src="<?= esc_url($resolved['url']); ?>" alt="<?= esc_attr($resolved['alt']); ?>" class="w-full h-full object-cover m-0!">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php // Smaller op mobiel/tablet dan de "w-60" vanaf "lg" hierboven: een
                // vaste 240px-brede kolom laat op een telefoon nog maar een fractie
                // van de breedte over voor de hoofdafbeelding (flex-1 hiernaast) —
                // precies het probleem dat responsiveness hier moet voorkomen. 
                ?>
                <div class="own-image-slider-thumbs swiper select-none w-20 sm:w-32 lg:w-60 shrink-0 h-full">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $image) :
                            $resolved = $get_image($image); ?>
                            <div class="swiper-slide">
                                <?php // alt="": dezelfde afbeelding staat al beschreven op de
                                // hoofdslide hiernaast — een tweede aankondiging op de
                                // miniatuur ervan is voor een screenreader-gebruiker alleen
                                // ruis.
                                ?>
                                <img src="<?= esc_url($resolved['url']); ?>" alt="" class="w-full h-full object-cover m-0!">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="swiper overflow-visible! select-none" data-own-slider>
                <div class="swiper-wrapper">
                    <?php foreach ($images as $image) :
                        $resolved = $get_image($image); ?>
                        <div class="swiper-slide h-auto!">
                            <img src="<?= esc_url($resolved['url']); ?>" alt="<?= esc_attr($resolved['alt']); ?>" class="w-full h-full object-cover aspect-3/2 m-0!">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-image-slider-pagination w-full text-center mt-2" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>