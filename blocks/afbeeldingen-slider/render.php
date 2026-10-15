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
            // Onder "lg" onder elkaar: hoofdafbeelding over de volle breedte, de
            // miniaturen als horizontale strook eronder (image-slider.js zet de
            // thumbs-Swiper pas vanaf 1024px op "direction: vertical"). Vanaf
            // "lg" naast elkaar, strook rechts — de hoofdslider staat al vóór de
            // strook in de DOM, dus geen "order"-override nodig. Geen
            // "overflow-visible!" hier (i.p.v. de standaardversie hieronder):
            // deze versie moet binnen zijn eigen kolom blijven.
            //
            // Hoogte: onder elkaar heeft de hoofdslider zelf "aspect-video" en
            // elke miniatuur "aspect-3/2". Naast elkaar ligt "aspect-video" op
            // de RIJ, met "h-full" op beide .swiper-containers: een verticale
            // Swiper heeft een vaststaande containerhoogte nodig. Zonder die
            // liep Swiper's hoogteberekening eerder vast en zette hij een
            // absurde inline hoogte op de slides (2^24px, de maximale
            // layoutgrootte van de browser).
            ?>
            <div class="flex flex-col gap-3 lg:flex-row lg:gap-4 lg:aspect-video">
                <?php // Afronding op de kolom, niet op de <img>: een afgeronde
                // afbeelding die door Swiper verschoven wordt, flikkerde aan de
                // hoeken. De container staat stil en knipt de slides af;
                // "isolate" laat dat afknippen ook in Safari werken, waar
                // overflow + border-radius anders getransformeerde kinderen
                // niet afknipt.
                ?>
                <div class="own-image-slider-main swiper select-none w-full aspect-video lg:aspect-auto lg:w-auto lg:flex-1 lg:min-w-0 lg:h-full overflow-hidden isolate rounded-xl" data-own-slider="thumbs">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $image) :
                            $resolved = $get_image($image);
                            $slide_position = ownframework_get_image_object_position($image['position'] ?? []); ?>
                            <div class="swiper-slide">
                                <img src="<?= esc_url($resolved['url']); ?>" alt="<?= esc_attr($resolved['alt']); ?>" class="w-full h-full object-cover m-0!" style="object-position: <?= esc_attr($slide_position); ?>;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="own-image-slider-thumbs swiper select-none w-full lg:w-60 lg:shrink-0 lg:h-full">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $image) :
                            $resolved = $get_image($image);
                            $slide_position = ownframework_get_image_object_position($image['position'] ?? []); ?>
                            <?php // Zelfde reden als de hoofdslider: de afronding op het
                            // eigen vakje van de miniatuur, niet op de <img>.
                            ?>
                            <div class="swiper-slide overflow-hidden isolate rounded-lg lg:rounded-xl">
                                <?php // alt="": dezelfde afbeelding staat al beschreven op de
                                // hoofdslide hiernaast — een tweede aankondiging op de
                                // miniatuur ervan is voor een screenreader-gebruiker alleen
                                // ruis.
                                ?>
                                <img src="<?= esc_url($resolved['url']); ?>" alt="" class="w-full h-full aspect-3/2 lg:aspect-auto object-cover m-0!" style="object-position: <?= esc_attr($slide_position); ?>;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="swiper overflow-visible! select-none" data-own-slider>
                <div class="swiper-wrapper">
                    <?php foreach ($images as $image) :
                        $resolved = $get_image($image);
                        $slide_position = ownframework_get_image_object_position($image['position'] ?? []); ?>
                        <div class="swiper-slide h-auto!">
                            <img src="<?= esc_url($resolved['url']); ?>" alt="<?= esc_attr($resolved['alt']); ?>" class="w-full h-full object-cover aspect-3/2 rounded-xl m-0!" style="object-position: <?= esc_attr($slide_position); ?>;">
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