<?php
defined('ABSPATH') || exit();

/**
 * Hero — altijd het eerste block op een pagina (block.json's "multiple":
 * false houdt 'm uit de inserter zodra er al één op de pagina staat).
 *
 * "mediaType" (none/image/slider/video) en "imageLayout" (background/side,
 * alleen relevant bij "image"/"none") zijn losse attributes i.p.v. één
 * samengevoegde "backgroundType" (oudere versie): side-by-side betekent
 * alleen iets bij een losse afbeelding of "geen media", niet bij een
 * slideshow of video. "none" is de default: een schone kleur/verloop-hero
 * uit het native "Kleuren"-paneel, zonder media.
 *
 * Plain block-attributes (zie block.json) + editor.js, geen SCF-velden — de
 * achtergrond-configuratie is simpel genoeg voor dit theme's gewone patroon
 * (zie acf/footer-cta/header/afbeeldingen-slider voor dezelfde aanpak).
 *
 * De inhoud zelf (kop, tekst, knoppen) is InnerBlocks, geen veld — core/
 * buttons geeft per knop al native stijl/kleur-opties. Werkt via SCF's eigen
 * <InnerBlocks />-placeholdertekst (letterlijke string hieronder, geen React-
 * component): SCF vervangt 'm automatisch door de daadwerkelijke inhoud,
 * front-end én editor-preview.
 *
 * Achtergrondkleur (native "Kleuren"-paneel) doet dubbele dienst: zonder
 * volle-breedte media is het de sectie-achtergrond, mét media is het de
 * overlay-kleur erover (zelfde patroon als core/cover) — geen los
 * "overlayColor"-veld nodig. Een verloop volgt alleen de eerste helft: dat
 * wordt nooit een overlay-tint, alleen ooit de sectie-achtergrond zelf.
 *
 * "align" biedt sinds het verwijderen van "full" uit supports.align (zie
 * blocks/cta, blocks/afbeeldingen-slider voor dezelfde stap) alleen nog ""
 * (inhoudsbreedte) of "wide" — de <section> zelf volgt dus altijd gewoon die
 * breedte, net als elk ander niet-full-uitgelijnd block. De achtergrond
 * (achtergrondkleur/-verloop, of de media bij "background"/"slider"/"video")
 * moet daarentegen wél altijd tot de randen van het scherm lopen, onafhankelijk
 * van die keuze — dat is nu een eigen, van WordPress' align-systeem los
 * $bleed_class-laagje (position:absolute, "left-1/2 w-screen -translate-x-1/2"
 * — de klassieke full-bleed-truc: 100vw breed, gecentreerd op het midden van
 * de <section> zelf, wat samenvalt met het midden van de viewport zolang de
 * section zelf gecentreerd in de pagina staat, wat bij "" en "wide" allebei
 * het geval is). Werkt zonder src/css/app.css's "html { overflow-x: clip }"
 * te moeten aanpassen: die regel bestaat al voor exact dit soort 100vw-content.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter: wptexturize() zet een cijfer direct gevolgd door een
 * aanhalingsteken om in een dubbele-prime-entity, wat het sluitende
 * aanhalingsteken kapotmaakt. En nooit een losse "[&>...]" Tailwind-variant
 * in een class-attribute — gebruik "[&_...]" (descendant) i.p.v. "[&>...]"
 * (direct-child) om het ">"-teken te vermijden dat wp_html_split() als
 * tag-einde leest. (Zie blocks/afbeeldingen-slider/render.php voor de volle
 * uitleg.)
 */

$media_type = $block['mediaType'] ?? 'none';
$image_layout = $block['imageLayout'] ?? 'background';
$height = $block['height'] ?? 'auto';
$overlay_opacity = max(0, min(100, (int) ($block['overlayOpacity'] ?? 40)));
$image_position = ($block['imagePosition'] ?? 'right') === 'left' ? 'left' : 'right';
$show_pagination = !empty($block['showPagination']);
$show_breadcrumbs = !empty($block['showBreadcrumbs']);
$pagination_color = $block['paginationColor'] ?: 'var(--wp--preset--color--primary)';
$slider_effect = ($block['sliderEffect'] ?? 'slide') === 'fade' ? 'fade' : 'slide';

/**
 * Haalt de huidige url/alt van een afbeelding op via het attachment-ID, met
 * terugval op de opgeslagen waarde voor een afbeelding zonder (geldig) ID.
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

// "side" is ook geldig bij mediaType "none" (kleur/verloop + optionele
// afbeelding naast de inhoud), niet alleen bij "image".
$is_side_layout = in_array($media_type, ['image', 'none'], true) && $image_layout === 'side';

// "image" zonder gekozen afbeelding gedraagt zich als "geen media" (geen
// overlay, geen witte tekst) i.p.v. een kapotte foto-hero. "none" telt hier
// nooit mee — die heeft per definitie nooit media.
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

// Zelfde patroon als $bg_value, voor het "Verloop"-tabblad — maar dit wordt
// nooit een overlay-tint (zie $style_parts hieronder), alleen ooit de
// sectie-achtergrond zelf.
if (!empty($block['style']['color']['gradient'])) {
    $gradient_value = $block['style']['color']['gradient'];
} elseif (!empty($block['gradient'])) {
    $gradient_value = 'var(--wp--preset--gradient--' . $block['gradient'] . ')';
} else {
    $gradient_value = '';
}

if (!empty($block['style']['color']['text'])) {
    $text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
    $text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
    $text_value = '';
}

// Witte tekst leest beter over foto/video zonder expliciete kleurkeuze.
if ($text_value === '' && $has_media_background) {
    $text_value = '#fff';
}

// Geen background-color/-image meer hier: die verhuizen naar het eigen
// full-bleed achtergrondlaagje hieronder (zie $bleed_class), zodat een effen
// kleur/verloop ook tot de randen van het scherm loopt i.p.v. te stoppen bij
// de section's eigen (align-afhankelijke) breedte. WordPress' eigen native
// "Kleuren"-support zet dezelfde background-color/-gradient nog steeds
// automatisch óók op de section zelf (via get_block_wrapper_attributes(),
// buiten dit bestand om) — onschadelijk, dat valt toch helemaal onder het
// bredere laagje erboven.
$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
// Altijd als laatste, nooit conditioneel: garandeert dat de style-attribute
// nooit op een cijfer eindigt (get_block_wrapper_attributes() strip zelf
// altijd de trailing ";", dus dat alleen voorkomt het probleem niet — zie de
// docblock hierboven).
$style_parts[] = 'box-sizing:border-box';

$align = $block['align'] ?? '';
// "own-hero" altijd als laatste class-token, zelfde reden als hierboven.
$align_class = $align !== '' ? 'align' . $align : '';

// Alleen bij align "full" annuleert WordPress' eigen negative margin de root-
// padding (useRootPaddingAwareAlignments, zie STRUCTURE.md "Page rendering")
// — bij "wide" en "" (geen uitlijning) staat de section al binnen die padding,
// met dezelfde breedte-/gutterregels als elk ander, niet-uitgelijnd block.
// .container hieronder mag dan niet nog een keer zijn eigen zijpadding
// toevoegen naast wat de section al van WordPress zelf krijgt.
$use_container = $align === 'full';

// $height_class gaat op een wrapper om .container, niet op de section zelf:
// zo kan "medium"/"large"/"screen" de inhoud centreren zonder de media-
// achtergrond (die de volle section-hoogte moet blijven vullen) mee te
// centreren.
// $vertical_align verschilt per layout: bij de side-layout bepaalt de
// afbeelding zelf de rijhoogte (grid's "items-stretch" hieronder), dus blijft
// "items-center" hier de hele grid centreren. Zonder afbeelding heeft
// .container zelf "items-stretch" nodig — anders heeft .max-w-2xl geen echte
// hoogte om het broodkruimelpad los van de kop/tekst/knoppen in te centreren.
$vertical_align = $is_side_layout ? 'items-center' : 'items-stretch';
$height_class = match ($height) {
    'medium' => 'min-h-[60vh] flex ' . $vertical_align . ' py-12 sm:py-16 lg:py-20',
    'large' => 'min-h-[85vh] flex ' . $vertical_align . ' py-12 sm:py-16 lg:py-20',
    'screen' => 'min-h-screen flex ' . $vertical_align,
    default => 'py-12 sm:py-16 lg:py-20',
};
$section_class = trim($align_class . ' relative own-hero');

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $section_class,
    'style' => implode(';', $style_parts),
]);

// Eigen full-bleed laagje voor de achtergrond (zie docblock hierboven) — de
// "overflow-hidden" die vroeger op de section zelf stond (om de media binnen
// de section's eigen box te houden) staat nu hier: dit laagje mag zelf wél
// buiten de section uitsteken (dat is precies het punt), maar de media
// erbínnen (object-cover-afbeelding/video) niet buiten dít laagje.
$bleed_class = 'absolute inset-y-0 left-1/2 w-screen -translate-x-1/2 overflow-hidden';

$overlay_color = $bg_value !== '' ? $bg_value : '#000';
$overlay_html = '<div class="z-2 inset-0 absolute" style="background-color:' . esc_attr($overlay_color) . ';opacity:' . esc_attr((string) ($overlay_opacity / 100)) . ';"></div>';

// Zelfde $bg_value/$gradient_value als hierboven, nu als losse style-string
// voor het full-bleed laagje bij een effen kleur/verloop-hero (geen media).
// Altijd als laatste "box-sizing:border-box", zelfde reden als bij
// $style_parts hierboven (wptexturize/cijfer-val, zie docblock).
$flat_bg_style_parts = [];
if ($bg_value !== '') {
    $flat_bg_style_parts[] = 'background-color:' . esc_attr($bg_value);
}
if ($gradient_value !== '') {
    $flat_bg_style_parts[] = 'background-image:' . esc_attr($gradient_value);
}
if (!empty($flat_bg_style_parts)) {
    $flat_bg_style_parts[] = 'box-sizing:border-box';
}

// In dezelfde kolom als de kop/tekst/knoppen, buiten de "flex-1 items-
// center"-div hieronder: zo centreert alleen die inhoud, en blijft het
// broodkruimelpad zelf bovenaan de kolom — bij de side-layout dankzij de
// grid's "items-stretch" op één lijn met de top van de afbeelding. "not-
// prose" voorkomt dat de <ol> de pagina-brede ".prose"-lijstopmaak overneemt
// (zie CLAUDE.md, zelfde reden als own-header/-footer).
$breadcrumbs = $show_breadcrumbs ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm', !empty($is_preview)) : '';

/**
 * Letterlijke placeholdertekst, geen React-component — zie de docblock
 * hierboven. Single-quoted HTML-attributen om de dubbele aanhalingstekens in
 * de JSON-waarden (template/allowedBlocks) niet te moeten escapen.
 */
$hero_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":1,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Beschrijving…"}],["core/buttons",{}]]\' allowedBlocks=\'["core/heading","core/paragraph","core/buttons","core/list"]\' templateLock=\'false\' />';
?>
<section <?= $wrapper_attributes; ?>>
    <?php if ($media_type === 'image' && $image_layout === 'background' && $background_image['url'] !== '') : ?>
        <div class="<?= $bleed_class; ?>">
            <img src="<?= esc_url($background_image['url']); ?>" alt="<?= esc_attr($background_image['alt']); ?>" class="absolute inset-0 w-full h-full object-cover m-0!">
            <?= $overlay_html; ?>
        </div>
    <?php elseif ($media_type === 'slider' && !empty($slider_images)) : ?>
        <div class="<?= $bleed_class; ?>">
            <?php // .own-hero-pagination moet een kind van .swiper blijven — Swiper's
            // eigen CSS voor de paginatie-positie gebruikt een direct-child-selector
            // die anders niet matcht. De overlay staat om dezelfde reden óók binnen
            // .swiper (niet als sibling erbuiten): dan is de stapelvolgorde gewoon
            // wrapper (z-index:1) → overlay (z-2) → paginatie (Swiper's eigen
            // z-index:10), geen aparte override nodig.
            // De klasse "swiper-pagination" moet er wél handmatig bij: Swiper voegt
            // die alleen automatisch toe aan een element dat het zélf aanmaakt, niet
            // aan een eigen pagination.el zoals hier — zonder die klasse (die
            // position:absolute;z-index:10 draagt) blijft dit statisch gepositioneerd
            // ná .swiper-wrapper en wordt het afgesneden door .swiper's eigen
            // overflow:hidden.
            ?>
            <div class="w-full h-full swiper" data-own-hero-slider="<?= esc_attr($slider_effect); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($slider_images as $image) :
                        $resolved = $get_image($image); ?>
                        <div class="swiper-slide">
                            <img src="<?= esc_url($resolved['url']); ?>" alt="<?= esc_attr($resolved['alt']); ?>" class="w-full h-full object-cover m-0!">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= $overlay_html; ?>
                <?php if ($show_pagination) : ?>
                    <div class="own-hero-pagination swiper-pagination w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($media_type === 'video' && $video_url !== '') : ?>
        <div class="<?= $bleed_class; ?>">
            <video autoplay muted loop playsinline data-own-hero-video <?= $poster_image['url'] !== '' ? 'poster="' . esc_url($poster_image['url']) . '"' : ''; ?> class="absolute inset-0 w-full h-full object-cover m-0!">
                <source src="<?= esc_url($video_url); ?>" type="video/mp4">
            </video>
            <?= $overlay_html; ?>
        </div>
    <?php elseif ($media_type === 'image' && $image_layout === 'background' && $background_image['url'] === '' && !empty($is_preview)) : ?>
        <?php // Alleen in de editor: laat zien wáár de afbeelding komt. Op de live
        // site is er bewust geen "lege" front-end variant.
        ?>
        <div class="<?= $bleed_class; ?>">
            <div class="bg-gray-100 text-gray-400 inset-0 absolute flex items-center justify-center">
                <div class="flex flex-col gap-2 items-center">
                    <?= ownframework_get_icon('image', 'w-10 h-10 inline-block'); ?>
                    <span class="text-sm"><?php esc_html_e('Kies een afbeelding in de zijbalk', 'own-framework'); ?></span>
                </div>
            </div>
        </div>
    <?php elseif (!empty($flat_bg_style_parts)) : ?>
        <?php // Geen media: een effen kleur/verloop-hero (of de side-layout, die zijn
        // afbeelding niet als achtergrond maar apart naast de inhoud toont — zie
        // $is_side_layout hieronder) loopt zo tóch tot de randen van het scherm
        // i.p.v. te stoppen bij de section's eigen align-breedte.
        ?>
        <div class="<?= $bleed_class; ?>" style="<?= esc_attr(implode(';', $flat_bg_style_parts)); ?>"></div>
    <?php endif; ?>

    <div class="<?= esc_attr($height_class); ?>">
        <div class="relative z-10<?= $use_container ? ' container' : ''; ?>">
            <?php if ($is_side_layout) : ?>
                <div class="grid lg:grid-cols-2 gap-6 lg:gap-10 items-stretch">
                    <?php // Order zonder "lg:"-prefix (i.p.v. alleen op desktop): anders
                    // stapelt dit op mobiel altijd in DOM-volgorde (kop/tekst boven de
                    // afbeelding), ongeacht de gekozen "Positie afbeelding" — die keuze
                    // moet ook op mobiel iets betekenen, niet alleen op desktop. ?>
                    <div class="<?= $image_position === 'left' ? 'order-2' : 'order-1'; ?> w-full h-full flex flex-col">
                        <?= $breadcrumbs; ?>
                        <div class="flex-1 flex items-center">
                            <div class="prose <?= $has_media_background ? 'prose-invert' : ''; ?> max-w-none">
                                <?= $hero_inner_blocks; ?>
                            </div>
                        </div>
                    </div>
                    <div class="<?= $image_position === 'left' ? 'order-1' : 'order-2'; ?> w-full">
                        <?php if ($background_image['url'] !== '') : ?>
                            <img src="<?= esc_url($background_image['url']); ?>" alt="<?= esc_attr($background_image['alt']); ?>" class="rounded-xl aspect-square lg:aspect-3/2 lg:h-full w-full object-cover m-0!">
                        <?php elseif (!empty($is_preview)) : ?>
                            <div class="bg-gray-100 text-gray-400 rounded-xl aspect-square lg:aspect-auto lg:h-full w-full flex items-center justify-center">
                                <div class="flex flex-col gap-2 items-center">
                                    <?= ownframework_get_icon('image', 'w-10 h-10 inline-block'); ?>
                                    <span class="text-sm"><?php esc_html_e('Kies een afbeelding in de zijbalk', 'own-framework'); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="max-w-2xl h-full flex flex-col">
                    <?= $breadcrumbs; ?>
                    <div class="flex-1 flex items-center">
                        <div class="prose <?= $has_media_background ? 'prose-invert' : ''; ?> max-w-none">
                            <?= $hero_inner_blocks; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>