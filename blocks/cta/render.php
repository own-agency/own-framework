<?php
defined('ABSPATH') || exit();

/**
 * Call to action — kop/tekst/knoppen (InnerBlocks) in een afgeronde kaart.
 * "align" biedt sinds het verwijderen van "full" uit supports.align (zie
 * acf/hero voor dezelfde stap) alleen nog "" (inhoudsbreedte) of "wide" — de
 * <section> zelf volgt dus altijd gewoon die breedte. $use_container/de losse
 * .container-markup hieronder bestaan nog uitsluitend voor content van vóór
 * die wijziging, met "full" nog opgeslagen als align-waarde (dat rendert nog
 * exact zoals het altijd deed) — een nieuwe kaart kan die combinatie niet meer
 * kiezen.
 *
 * "fullWidth" (editor.js's "Achtergrond over volle breedte") is een OPTIONELE
 * toggle: standaard blijft de kaart een ingesloten, afgeronde "boxed" banner
 * op de section's eigen (align-)breedte — dat werkt al vanzelf, geen aparte
 * code voor nodig. Aangevinkt (en niet de hierboven genoemde legacy-combinatie)
 * gebruikt dit block acf/hero's eigen full-bleed-truc ($bleed_class): een
 * apart, van de kaart los achtergrondlaagje (effen kleur, of de afbeelding bij
 * "background") dat altijd 100vw beslaat, terwijl de kaart zelf (nu zonder
 * rounded-2xl/shadow-sm — dat oogt aan de randen van het scherm juist kapot,
 * en zonder eigen achtergrond, die zit nu in het laagje erachter) gewoon op de
 * section's align-breedte blijft staan. $use_bleed hieronder is precies die
 * voorwaarde (fullWidth aan, geen legacy align="full").
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
 * hero's paginatie-/navigatieknoppenkleur. Geen eigen "cardTextColor"
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

$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';
$section_class = trim($align_class . ' relative ' . 'own-cta');

// Legacy: alleen nog waar bij content van vóór "full" uit supports.align
// verdween, met die waarde nog opgeslagen (zie docblock hierboven). Alleen
// dan krijgt de section WordPress' eigen negative-margin die de root-padding
// annuleert (useRootPaddingAwareAlignments, zie STRUCTURE.md "Page
// rendering"), en is er nog een eigen .container nodig om de kaart terug te
// brengen tot een leesbare breedte.
$use_container = $align === 'full';

// De voorwaarde voor acf/hero's full-bleed-truc (zie docblock hierboven):
// "Achtergrond over volle breedte" aan, én niet de legacy-combinatie
// hierboven (die haalt haar volle breedte al van de section zelf, geen
// apart laagje nodig).
$use_bleed = $full_width && !$use_container;

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

// Geen rounded-2xl/shadow-sm bij "volle breedte": een kaart die tot de
// randen van het scherm loopt, oogt met afgeronde hoeken/schaduw juist kapot
// i.p.v. bewust — dat is precies het verschil tussen een ingesloten kaart en
// een edge-to-edge banner. Bij $use_bleed zit de afbeelding niet meer in de
// kaart zelf (die verhuist naar het losse achtergrondlaagje hieronder), dus
// ook geen overflow-hidden meer nodig om 'm binnen de kaart te houden.
$card_class = trim(
    'own-cta-card relative ' .
        (!$full_width ? 'rounded-2xl shadow-sm ' : '') .
        ($has_media_background && !$use_bleed ? 'overflow-hidden' : '')
);

$overlay_color = $bg_value !== '' ? $bg_value : '#000';
$overlay_html = '<div class="z-2 inset-0 absolute" style="background-color:' . esc_attr($overlay_color) . ';opacity:' . esc_attr((string) ($overlay_opacity / 100)) . ';"></div>';

// Zelfde full-bleed laagje als acf/hero (zie docblock hierboven) — alleen
// gerenderd als $use_bleed, dus puur optioneel via de "Achtergrond over volle
// breedte"-toggle.
$bleed_class = 'absolute inset-y-0 left-1/2 w-screen -translate-x-1/2 overflow-hidden';
$bleed_style_parts = [];
if (!$has_media_background && $bg_value !== '') {
    $bleed_style_parts[] = 'background-color:' . esc_attr($bg_value);
}
$bleed_style_parts[] = 'box-sizing:border-box';

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg.
 */
$cta_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":2,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Beschrijving…"}],["core/buttons",{}]]\' allowedBlocks=\'["core/heading","core/paragraph","core/buttons","core/list"]\' templateLock=\'false\' />';
?>
<section <?= $wrapper_attributes; ?>>
    <?php if ($use_bleed) : ?>
        <div class="<?= $bleed_class; ?>" style="<?= esc_attr(implode(';', $bleed_style_parts)); ?>">
            <?php if ($has_media_background) : ?>
                <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="absolute inset-0 w-full h-full object-cover m-0!">
                <?= $overlay_html; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($use_container && !$full_width) : ?><div class="container"><?php endif; ?>
        <div class="<?= esc_attr($card_class); ?>" style="<?= esc_attr(implode(';', $card_style_parts)); ?>">
            <?php if ($has_media_background && !$use_bleed) : ?>
                <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="absolute inset-0 w-full h-full object-cover m-0!">
                <?= $overlay_html; ?>
            <?php endif; ?>

            <?php
            // Bij "volle breedte" is DIT de kaart zelf (achtergrond/afbeelding loopt
            // tot de randen), en schuift .container hier naar binnen om alleen de
            // inhoud smal te houden — zonder "volle breedte" is de kaart zelf al
            // ingesloten in de buitenste .container hierboven, dus hier is geen extra
            // container nodig. Bij align "wide"/"" ($use_container false) vervalt
            // .container aan beide kanten sowieso — zie de docblock bij $use_container
            // hierboven.
            ?>
            <?php if ($use_container && $full_width) : ?><div class="relative z-10 container"><?php endif; ?>

                <?php if ($is_side_layout) : ?>
                    <div class="flex flex-col lg:flex-row gap-6 sm:gap-8 lg:gap-12 p-4 sm:p-6 lg:p-8 items-center">
                        <div class="<?= $image_position === 'left' ? 'order-1' : 'order-2'; ?> shrink-0 w-full max-w-72 sm:max-w-96 lg:w-60 lg:max-w-none">
                            <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="rounded-xl aspect-square w-full object-cover m-0!">
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

                <?php if ($use_container && $full_width) : ?>
                </div><?php endif; ?>
        </div>
        <?php if ($use_container && !$full_width) : ?>
        </div><?php endif; ?>
</section>