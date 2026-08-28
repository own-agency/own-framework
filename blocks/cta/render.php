<?php
defined('ABSPATH') || exit();

/**
 * Call to action — kop/tekst/knoppen (InnerBlocks) in een afgeronde kaart.
 * "align" heeft drie niveaus — "" (Smal), "wide" (Normaal, de default) of
 * "full" (Breed, een derde, nog steeds ingesloten breedte-niveau — GEEN
 * edge-to-edge meer, zie CLAUDE.md's "Alignment, width and full-bleed
 * backgrounds") — en de <section> zelf volgt gewoon die breedte, alle drie op
 * precies dezelfde manier: geen los "container"-geval meer voor "full", dat
 * bestond alleen zolang "full" nog edge-to-edge betekende.
 *
 * "fullWidth" (editor.js's "Achtergrond over volle breedte") is een OPTIONELE
 * toggle, los van "align": standaard blijft de kaart een ingesloten,
 * afgeronde "boxed" banner op de section's eigen (align-)breedte — dat werkt
 * al vanzelf, geen aparte code voor nodig. Aangevinkt gebruikt dit block acf/
 * hero's eigen full-bleed-truc ($bleed_class): een apart, van de kaart los
 * achtergrondlaagje (effen kleur, of de afbeelding bij "background") dat
 * altijd 100vw beslaat, terwijl de kaart zelf (nu zonder rounded-2xl/eigen
 * rand — dat oogt aan de randen van het scherm juist kapot, en zonder eigen
 * achtergrond, die zit nu in het laagje erachter) gewoon op de section's
 * align-breedte blijft staan.
 *
 * "imageLayout" (side/background) is hetzelfde concept als acf/hero's eigen
 * imageLayout: "side" toont de gekozen afbeelding naast de inhoud (optioneel
 * — zonder afbeelding is de kaart gewoon een effen kleurvak met de inhoud
 * alleen), "background" gebruikt de afbeelding als kaart-achtergrond met een
 * kleurtint erover, zoals acf/hero's eigen background-variant. Geen
 * "mediaType" nodig zoals hero — hier is maar één media-soort (een losse
 * afbeelding), geen slider/video.
 *
 * Twee onafhankelijke kleurlagen, met opzet niet dezelfde: het native
 * "Kleuren"-paneel (supports.color hieronder) kleurt de VOLLE section — dat
 * gaat via get_block_wrapper_attributes(), WordPress' eigen color-support
 * voegt daar automatisch background-color/tekstkleur aan toe, ongeacht wat
 * dit bestand zelf doet. Voor de afgeronde kaart ERBINNEN is dat native
 * paneel dus niet bruikbaar (het raakt nooit de kaart, alleen de section) —
 * "cardBackgroundColor" is daarom een EIGEN attribute + een eigen "Kleur
 * kaart"-paneel (editor.js, PanelColorSettings), zelfde patroon als acf/
 * hero's paginatie-/navigatieknoppenkleur.
 *
 * De SECTIE's eigen native achtergrond krijgt, net als bij acf/feitjes-
 * slider/acf/content-blokken-slider/acf/afbeeldingen-slider/acf/logo-slider,
 * ALTIJD zijn eigen full-bleed-laagje ($section_bleed_style_parts,
 * ownframework_full_bleed_class()) — ONVOORWAARDELIJK, niet afhankelijk van
 * de kaart's eigen "Achtergrond over volle breedte"-toggle ($use_bleed)
 * hierboven: dat is een apart mechanisme voor of de KAART zelf edge-to-edge
 * oogt, geen voorwaarde om de sectie's eigen achtergrondkleur al tot de
 * schermrand te laten lopen. Vóór deze fix stopte een via het native
 * "Kleuren"-paneel gekozen kleur bij de sectie's eigen align-breedte,
 * inconsistent met elke andere gekleurde sectie op de site — zelfde bug/fix
 * als acf/logo-slider eerder had (zie CLAUDE.md's eigen "Alignment, width
 * and full-bleed backgrounds"-sectie). Geen eigen "cardTextColor"
 * ernaast: de InnerBlocks-inhoud (kop/tekst/knoppen) heeft al per element
 * een eigen native tekstkleur-keuze, en de section's eigen native tekstkleur
 * cascadeert al gewoon door tot in de kaart — een derde, kaart-specifieke
 * tekstkleur zou daar alleen maar bovenop komen zonder iets toe te voegen.
 * Op een fotoachtergrond ("background", zie hieronder) wordt tekst sowieso
 * altijd wit via prose-invert, onafhankelijk van welke tekstkleur er ook
 * gekozen is — leesbaarheid boven een foto is geen keuze, dat moet gewoon.
 *
 * De inhoud zelf (kop, tekst, knoppen) is InnerBlocks, geen veld — core/
 * buttons geeft per knop al native stijl/kleur-opties. Zie acf/hero's eigen
 * docblock voor de volle uitleg van SCF's <InnerBlocks />-vervang-truc.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter of "!": wptexturize() zet een cijfer direct gevolgd
 * door een aanhalingsteken om in een dubbele-prime-entity, wat het sluitende
 * aanhalingsteken kapotmaakt. Class-lijsten hieronder zijn daarom zo
 * geordend dat ze altijd op een letter (of "!") eindigen, nooit op een cijfer.
 */

$image_layout = ($block['imageLayout'] ?? 'side') === 'background' ? 'background' : 'side';
$image_position = ($block['imagePosition'] ?? 'right') === 'left' ? 'left' : 'right';
$overlay_opacity = max(0, min(100, (int) ($block['overlayOpacity'] ?? 40)));
$full_width = !empty($block['fullWidth']);

// ownframework_resolve_block_image() — inc/functions/image-helpers.php,
// gedeeld met acf/content-blok.
$image = ownframework_resolve_block_image($block['image'] ?? []);
$has_image = $image['url'] !== '';

// ownframework_get_image_object_position() — inc/functions/image-helpers.php,
// gedeeld met acf/hero, acf/content-blok, acf/afbeeldingen-slider en acf/verhalen.
$image_position = ownframework_get_image_object_position($block['imageFocalPoint'] ?? []);

// "background" zonder gekozen afbeelding gedraagt zich als "side" zonder
// afbeelding — gewoon een effen kleurvak, geen kapotte lege achtergrond
// (zelfde reden als acf/hero's eigen $has_media_background).
$has_media_background = $image_layout === 'background' && $has_image;
$is_side_layout = $image_layout === 'side' && $has_image;

// Al resolved naar een "var(--wp--preset--color--<slug>)" (of een losse hex-
// waarde) door editor.js's eigen resolveToPresetVar() — hier gewoon direct
// bruikbaar, geen "var(...)"-wrapping meer nodig zoals bij een native
// backgroundColor-slug.
$bg_value = $block['cardBackgroundColor'] ?: '';

// De SECTION's eigen native "Kleuren"-paneel achtergrond (supports.color
// hierboven in block.json) — een volledig los kleurlaagje van $bg_value
// hierboven (dat is de KAART's eigen "cardBackgroundColor", een apart
// attribuut/paneel, zie docblock). Deze sectie-achtergrond kreeg voorheen
// GEEN full-bleed-laagje: get_block_wrapper_attributes() zet 'm gewoon
// rechtstreeks op de sectie, die op zijn eigen align-breedte blijft — net
// zoals acf/logo-slider's achtergrond dat deed vóór die fix. Elke andere
// gekleurde sectie op de site (feitjes-slider, content-blokken-slider,
// afbeeldingen-slider, logo-slider) loopt via ownframework_full_bleed_
// background()/_class() altijd door tot de echte schermrand, ongeacht welke
// (optionele) toggle een block verder nog heeft — de kaart's eigen "Achtergrond
// over volle breedte"-toggle hieronder is een ANDER, bewust apart mechanisme
// (het bepaalt of de KAART zelf edge-to-edge oogt), geen voorwaarde voor de
// SECTIE's eigen achtergrondkleur om al tot de schermrand te lopen.
if (!empty($block['style']['color']['background'])) {
    $section_bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
    $section_bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
    $section_bg_value = '';
}

if (!empty($block['style']['color']['gradient'])) {
    $section_gradient_value = $block['style']['color']['gradient'];
} elseif (!empty($block['gradient'])) {
    $section_gradient_value = 'var(--wp--preset--gradient--' . $block['gradient'] . ')';
} else {
    $section_gradient_value = '';
}

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';
$section_class = trim($align_class . ' relative ' . 'own-cta');

// De voorwaarde voor acf/hero's full-bleed-truc (zie docblock hierboven):
// gewoon de "Achtergrond over volle breedte"-toggle zelf.
$use_bleed = $full_width;

$card_style_parts = [];
if (!$has_media_background && !$use_bleed && $bg_value !== '') {
    $card_style_parts[] = 'background-color:' . esc_attr($bg_value);
}
// Altijd als laatste, nooit conditioneel: garandeert dat de style-attribute
// nooit op een cijfer eindigt (zie de docblock hierboven).
$card_style_parts[] = 'box-sizing:border-box';

// "style" hier is bewust maar één, vaste, ongevaarlijke declaratie: het
// native "Kleuren"-paneel (supports.color) voegt zijn eigen background-
// color/tekstkleur automatisch AAN dit style-attribute toe, en die editor-
// gekozen hexkleur kan zelf op een cijfer eindigen (bv. "#062b20") — dezelfde
// wptexturize-val als in de docblock hierboven, nu via WordPress' eigen
// auto-toegevoegde stijl i.p.v. iets wat dit bestand zelf samenstelt.
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $section_class,
    'style' => 'box-sizing:border-box',
]);

// Geen rounded-2xl/rand bij "volle breedte": een kaart die tot de randen van
// het scherm loopt, oogt met afgeronde hoeken/rand juist kapot i.p.v. bewust
// — dat is precies het verschil tussen een ingesloten kaart en een edge-to-
// edge banner. Bij $use_bleed zit de afbeelding niet meer in de kaart zelf
// (die verhuist naar het losse achtergrondlaagje hieronder), dus ook geen
// overflow-hidden meer nodig om 'm binnen de kaart te houden.
// "own-surface" (app.css), niet "shadow-sm": dezelfde rand-zonder-schaduw als
// acf/zijbalk-navigatie/acf/vacatures/acf/agenda/acf/content-blok's eigen
// kaarten — zie die class's eigen comment voor de volle "geen shadow, tenzij
// echt zwevend"-redenering. Alleen zonder een eigen achtergrond op de kaart
// (geen cardBackgroundColor, geen media-achtergrond): die achtergrond geeft
// de kaart al genoeg afbakening tegen de pagina, een rand erbovenop oogt dan
// dubbelop — en bij een media-achtergrond zou de rand net zo goed om de
// AFBEELDING heen lopen als om de kaart, wat nooit netjes oogt (zelfde
// redenering als acf/content-blok's eigen "imageFullWidth"-geval).
$card_class = trim(
    'own-cta-card relative ' .
        (!$full_width ? 'rounded-2xl ' : '') .
        (!$full_width && $bg_value === '' && !$has_media_background ? 'own-surface ' : '') .
        ($has_media_background && !$use_bleed ? 'overflow-hidden' : '')
);

$overlay_color = $bg_value !== '' ? $bg_value : '#000';
$overlay_html = '<div class="z-2 inset-0 absolute" style="background-color:' . esc_attr($overlay_color) . ';opacity:' . esc_attr((string) ($overlay_opacity / 100)) . ';"></div>';

// Zelfde full-bleed laagje als acf/hero (zie docblock hierboven) — alleen
// gerenderd als $use_bleed, dus puur optioneel via de "Achtergrond over volle
// breedte"-toggle.
$bleed_class = ownframework_full_bleed_class();
$bleed_style_parts = [];
if (!$has_media_background && $bg_value !== '') {
    $bleed_style_parts[] = 'background-color:' . esc_attr($bg_value);
}
$bleed_style_parts[] = 'box-sizing:border-box';

// De SECTIE's eigen achtergrondlaagje (zie $section_bg_value's docblock
// hierboven) — ONVOORWAARDELIJK, niet achter $use_bleed: dit is niet de
// kaart se eigen "volle breedte"-keuze, gewoon dezelfde altijd-actieve
// full-bleed behandeling die elke andere gekleurde sectie op de site al
// krijgt. Leeg als er geen achtergrond/verloop gekozen is, zodat de div
// hieronder simpelweg niet gerenderd wordt (net als
// ownframework_full_bleed_background() zelf bij een lege kleur zou doen —
// hier met een handmatige array i.p.v. die helper, omdat een verloop
// (gradient) daar geen parameter voor heeft, zelfde aanpak als acf/
// vacature-hero's eigen $flat_bg_style_parts).
$section_bleed_style_parts = [];
if ($section_bg_value !== '') {
    $section_bleed_style_parts[] = 'background-color:' . esc_attr($section_bg_value);
}
if ($section_gradient_value !== '') {
    $section_bleed_style_parts[] = 'background-image:' . esc_attr($section_gradient_value);
}
if (!empty($section_bleed_style_parts)) {
    $section_bleed_style_parts[] = 'box-sizing:border-box';
}

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg.
 */
$cta_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":2,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Beschrijving…"}],["core/buttons",{}]]\' allowedBlocks=\'["core/heading","core/paragraph","core/buttons","core/list"]\' templateLock=\'false\' />';
?>
<section <?= $wrapper_attributes; ?>>
    <?php if (!empty($section_bleed_style_parts)) : ?>
        <div class="<?= $bleed_class; ?>" style="<?= esc_attr(implode(';', $section_bleed_style_parts)); ?>"></div>
    <?php endif; ?>
    <?php if ($use_bleed) : ?>
        <div class="<?= $bleed_class; ?>" style="<?= esc_attr(implode(';', $bleed_style_parts)); ?>">
            <?php if ($has_media_background) : ?>
                <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="absolute inset-0 w-full h-full object-cover m-0!" style="object-position: <?= esc_attr($image_position); ?>;">
                <?= $overlay_html; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="<?= esc_attr($card_class); ?>" style="<?= esc_attr(implode(';', $card_style_parts)); ?>">
        <?php if ($has_media_background && !$use_bleed) : ?>
            <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="absolute inset-0 w-full h-full object-cover m-0!">
            <?= $overlay_html; ?>
        <?php endif; ?>

        <?php if ($is_side_layout) : ?>
            <div class="flex flex-col lg:flex-row gap-6 sm:gap-8 lg:gap-12 p-4 sm:p-6 lg:p-8 items-center">
                <div class="<?= $image_position === 'left' ? 'order-1' : 'order-2'; ?> shrink-0 w-full max-w-72 sm:max-w-96 lg:w-60 lg:max-w-none">
                    <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="rounded-xl aspect-square w-full object-cover m-0!" style="object-position: <?= esc_attr($image_position); ?>;">
                </div>
                <div class="<?= $image_position === 'left' ? 'order-2' : 'order-1'; ?> w-full">
                    <div class="prose max-w-none">
                        <?= $cta_inner_blocks; ?>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="p-4 sm:p-6 lg:p-8 z-10 relative">
                <div class="max-w-2xl">
                    <div class="<?= trim('prose ' . ($has_media_background ? 'prose-invert ' : '') . 'max-w-none'); ?>">
                        <?= $cta_inner_blocks; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>