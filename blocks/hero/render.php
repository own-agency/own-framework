<?php
defined('ABSPATH') || exit();

/**
 * Hero — altijd het eerste block op een pagina (block.json's "multiple":
 * false houdt 'm uit de inserter zodra er al één op de pagina staat).
 * "mediaType" (image/slider/video) en "imageLayout" (background/side,
 * alleen relevant bij mediaType "image") zijn losse keuzes: een side-by-side
 * layout betekent alleen iets bij een losse afbeelding, niet bij een
 * slideshow of video — vandaar twee attributes i.p.v. één "backgroundType"
 * met 5 samengevoegde opties (oudere versie van dit block). Er is bewust
 * geen "geen afbeelding"-keuze: mediaType "image" zonder gekozen afbeelding
 * geeft al exact dat resultaat (zie $has_media_background hieronder, die op
 * daadwerkelijk aanwezige media checkt, niet op de gekozen mediaType-naam).
 * Plain block-attributes (zie block.json) + editor.js, geen SCF-velden — de
 * achtergrond-configuratie is simpel genoeg voor dit theme's gewone patroon
 * (zie acf/footer-cta/header/afbeeldingen-slider voor dezelfde aanpak).
 *
 * De inhoud zelf (kop, tekst, knoppen) is InnerBlocks, geen veld: een editor
 * sleept core/heading, core/paragraph en core/buttons rechtstreeks op
 * canvas — core/buttons geeft per knop al native stijl/kleur-opties, geen
 * eigen "knoppen"-veld nodig. Dit werkt via SCF's eigen <InnerBlocks />-
 * placeholdertekst (letterlijke tekst hieronder, geen React-component): SCF
 * vervangt 'm zowel op de front-end als in de editor-preview automatisch
 * door de daadwerkelijke inner-blocks-inhoud — blockVersion 3 (block.json)
 * is hiervoor genoeg, ook zonder acf-json-veldgroep en zonder eigen edit.js.
 *
 * Achtergrondkleur (native "Kleuren"-paneel) doet dubbele dienst: zonder
 * volle-breedte media (geen media gekozen, of layout "side") is het gewoon
 * de sectie-achtergrond, mét volle-breedte media is het de kleur van de
 * overlay erover (zelfde patroon als core/cover's eigen overlay-kleur) —
 * geen los "overlayColor"-veld nodig.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter: wptexturize() (draait op alle blockoutput via
 * the_content) zet een cijfer direct gevolgd door een aanhalingsteken om in
 * een dubbele-prime-entity, wat het sluitende aanhalingsteken van de HTML-
 * attribute kapotmaakt. En nooit een losse "[&>...]" Tailwind-variant in een
 * class-attribute: wp_html_split() (wptexturize's tag-tokenizer) zoekt naar
 * het eerste letterlijke ">" na een "<", ook als dat ">" middenin een
 * attribute-waarde staat — gebruik "[&_...]" (spatie/descendant) in plaats
 * van "[&>...]" (direct-child) om dat ">"-teken te vermijden.
 * (Zie blocks/afbeeldingen-slider/render.php voor de volle uitleg — allebei
 * de traps zijn daar de hard weg geleerd.)
 */

$media_type = $block['mediaType'] ?? 'image';
$image_layout = $block['imageLayout'] ?? 'background';
$height = $block['height'] ?? 'auto';
$overlay_opacity = max(0, min(100, (int) ($block['overlayOpacity'] ?? 40)));
$image_position = ($block['imagePosition'] ?? 'right') === 'left' ? 'left' : 'right';
$show_pagination = !empty($block['showPagination']);
$pagination_color = $block['paginationColor'] ?: 'var(--wp--preset--color--primary)';
$slider_effect = ($block['sliderEffect'] ?? 'slide') === 'fade' ? 'fade' : 'slide';

/**
 * Haalt de huidige url/alt van een afbeelding op via het attachment-ID.
 * Valt terug op de opgeslagen waarde uit de block-attributes voor een
 * afbeelding zonder (geldig) ID. Ook geschikt voor een los single-image-
 * attribute (backgroundImage/posterImage), niet alleen voor een lijst.
 */
$get_image = function (array $image): array {
    $id = (int) ($image['id'] ?? 0);
    return [
        'url' => $id ? (wp_get_attachment_image_url($id, 'full') ?: '') : ($image['url'] ?? ''),
        'alt' => $id ? trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)) : ($image['alt'] ?? ''),
    ];
};

$background_image = $get_image($block['backgroundImage'] ?? []);
$poster_image = $get_image($block['posterImage'] ?? []);
$slider_images = $block['sliderImages'] ?? [];

$video_id = (int) ($block['videoId'] ?? 0);
$video_url = $video_id ? (wp_get_attachment_url($video_id) ?: '') : trim($block['videoUrl'] ?? '');

$is_side_layout = $media_type === 'image' && $image_layout === 'side';

// Checkt of er daadwerkelijk volle-breedte media is, niet alleen of die
// mediaType geselecteerd staat — "image" zonder gekozen afbeelding moet zich
// exact als "geen media" gedragen (geen overlay, geen geforceerde witte
// tekst, gewoon de sectie-achtergrondkleur), niet als een kapotte foto-hero.
$has_media_background = !$is_side_layout && (
    ($media_type === 'image' && $background_image['url'] !== '')
    || ($media_type === 'slider' && !empty($slider_images))
    || ($media_type === 'video' && $video_url !== '')
);

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

// Zonder expliciete keuze leest witte tekst beter over een foto/video dan de
// sitebrede standaardkleur — alleen relevant zodra er ook echt media achter
// de tekst zit.
if ($text_value === '' && $has_media_background) {
    $text_value = '#fff';
}

$style_parts = [];
// Achtergrondkleur geldt zodra er GEEN volle-breedte media is (dus ook bij
// layout "side", of bij "image"/"slider"/"video" zonder daadwerkelijk
// gekozen media) — bij volle-breedte media is dezelfde kleur de overlay-tint
// (zie $overlay_color hieronder), niet de section-achtergrond zelf.
if (!$has_media_background && $bg_value !== '') {
    $style_parts[] = 'background-color:' . esc_attr($bg_value);
}
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
// Altijd als laatste toevoegen (nooit conditioneel): een editor kan een
// custom hexkleur kiezen die op een cijfer eindigt (bv. "#062b20"), en
// get_block_wrapper_attributes() strip zelf altijd een trailing ";" (dus die
// truc werkt hier niet) — een vaste, ongevaarlijke laatste declaratie
// garandeert dat de style-attribute nooit op dat cijfer eindigt.
$style_parts[] = 'box-sizing:border-box';

$align = $block['align'] ?? 'full';
$align_class = $align !== '' ? 'align' . $align : '';

// "own-hero" altijd als laatste token: garandeert dat de class-attribute
// nooit op een cijfer eindigt, ongeacht welke dynamische klasses (align,
// hoogte) ervoor komen — zie de docblock hierboven.
// "medium"/"large" behouden de padding náást de minimumhoogte: bij weinig
// inhoud garandeert min-h het gevoel van een hero, bij veel inhoud (die de
// minimumhoogte toch al overschrijdt) blijft er nog steeds lucht boven/onder.
$height_class = match ($height) {
    'medium' => 'min-h-[60vh] flex items-center py-20 sm:py-24',
    'large' => 'min-h-[85vh] flex items-center py-20 sm:py-24',
    'screen' => 'min-h-screen flex items-center',
    default => 'py-20 sm:py-24 lg:py-32',
};
$section_class = trim($align_class . ' relative ' . ($has_media_background ? 'overflow-hidden ' : '') . $height_class . ' own-hero');

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $section_class,
    'style' => implode(';', $style_parts),
]);

$overlay_color = $bg_value !== '' ? $bg_value : '#000';

/**
 * Letterlijke placeholdertekst, geen React-component — SCF vervangt 'm
 * (regex op de string "<InnerBlocks ... />") door de daadwerkelijke inner-
 * blocks-inhoud, zowel op de front-end als in de editor-preview. Single-
 * quoted HTML-attributen om de dubbele aanhalingstekens in de JSON-waarden
 * (template/allowedBlocks) niet te moeten escapen — ACF's eigen gedocumen-
 * teerde schrijfwijze hiervoor.
 */
$hero_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":1,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Beschrijving…"}],["core/buttons",{}]]\' allowedBlocks=\'["core/heading","core/paragraph","core/buttons","core/list"]\' templateLock=\'false\' />';
?>
<section <?= $wrapper_attributes; ?>>
    <?php if ($media_type === 'image' && $image_layout === 'background' && $background_image['url'] !== '') : ?>
        <img src="<?= esc_url($background_image['url']); ?>" alt="<?= esc_attr($background_image['alt']); ?>" class="absolute inset-0 w-full h-full object-cover m-0!">
        <div class="z-2 inset-0 absolute" style="background-color:<?= esc_attr($overlay_color); ?>;opacity:<?= esc_attr((string) ($overlay_opacity / 100)); ?>;"></div>
    <?php elseif ($media_type === 'slider' && !empty($slider_images)) : ?>
        <div class="inset-0 absolute">
            <?php // .own-hero-pagination moet een kind van .swiper blijven (net als bij
            // acf/afbeeldingen-slider): Swiper's eigen positionerings-CSS gebruikt een
            // direct-child-selector ("swiper-horizontal>.swiper-pagination-bullets"),
            // die simpelweg niet matcht zodra dit ergens anders in de DOM staat.
            //
            // De overlay staat daarom OOK binnen .swiper (niet als sibling erbuiten,
            // zoals bij de "image"/"video"-varianten): .swiper krijgt via Swiper's
            // eigen CSS zelf een stacking context (position + expliciete z-index),
            // waardoor het als geheel altijd vóór/achter een sibling-overlay staat —
            // wat de paginatie ERBINNEN zou meeslepen naar dezelfde kant als de
            // afbeeldingen, hoe je het ook draait. Als sibling van .swiper-wrapper
            // binnen diezelfde context is dit gewoon een normaal stapelprobleem: de
            // wrapper (Swiper's eigen z-index:1), dan deze overlay (z-2), dan de
            // paginatie (Swiper's eigen standaard z-index:10) — geen aparte
            // z-index-override op .swiper zelf meer nodig. ?>
            <div class="w-full h-full swiper" data-own-hero-slider="<?= esc_attr($slider_effect); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($slider_images as $image) :
                        $resolved = $get_image($image); ?>
                        <div class="swiper-slide">
                            <img src="<?= esc_url($resolved['url']); ?>" alt="<?= esc_attr($resolved['alt']); ?>" class="w-full h-full object-cover m-0!">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="z-2 inset-0 absolute" style="background-color:<?= esc_attr($overlay_color); ?>;opacity:<?= esc_attr((string) ($overlay_opacity / 100)); ?>;"></div>
                <?php if ($show_pagination) : ?>
                    <div class="own-hero-pagination w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($media_type === 'video' && $video_url !== '') : ?>
        <video autoplay muted loop playsinline data-own-hero-video <?= $poster_image['url'] !== '' ? 'poster="' . esc_url($poster_image['url']) . '"' : ''; ?> class="absolute inset-0 w-full h-full object-cover">
            <source src="<?= esc_url($video_url); ?>" type="video/mp4">
        </video>
        <div class="z-2 inset-0 absolute" style="background-color:<?= esc_attr($overlay_color); ?>;opacity:<?= esc_attr((string) ($overlay_opacity / 100)); ?>;"></div>
    <?php elseif ($media_type === 'image' && $image_layout === 'background' && $background_image['url'] === '' && !empty($is_preview)) : ?>
        <?php // Alleen in de editor: laat zien wáár de afbeelding komt i.p.v. gewoon niets tonen. Op de live site verdwijnt dit vanzelf zodra er een afbeelding gekozen is — er is hier bewust geen "lege" front-end variant. 
        ?>
        <div class="bg-gray-100 text-gray-400 inset-0 absolute flex items-center justify-center">
            <div class="flex flex-col gap-2 items-center">
                <?= ownframework_get_icon('image', 'w-10 h-10 inline-block'); ?>
                <span class="text-sm"><?php esc_html_e('Kies een afbeelding in de zijbalk', 'own-framework'); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="relative z-10 container">
        <?php if ($is_side_layout) : ?>
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div class="<?= $image_position === 'left' ? 'lg:order-2' : 'lg:order-1'; ?> w-full">
                    <div class="prose <?= $has_media_background ? 'prose-invert' : ''; ?> max-w-none">
                        <?= $hero_inner_blocks; ?>
                    </div>
                </div>
                <div class="<?= $image_position === 'left' ? 'lg:order-1' : 'lg:order-2'; ?> w-full">
                    <?php if ($background_image['url'] !== '') : ?>
                        <img src="<?= esc_url($background_image['url']); ?>" alt="<?= esc_attr($background_image['alt']); ?>" class="rounded-2xl aspect-square lg:aspect-auto lg:h-full w-full object-cover">
                    <?php elseif (!empty($is_preview)) : ?>
                        <div class="bg-gray-100 text-gray-400 rounded-2xl aspect-square lg:aspect-auto lg:h-full w-full flex items-center justify-center">
                            <div class="flex flex-col gap-2 items-center">
                                <?= ownframework_get_icon('image', 'w-10 h-10 inline-block'); ?>
                                <span class="text-sm"><?php esc_html_e('Kies een afbeelding in de zijbalk', 'own-framework'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="max-w-2xl">
                <div class="prose <?= $has_media_background ? 'prose-invert' : ''; ?> max-w-none">
                    <?= $hero_inner_blocks; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>