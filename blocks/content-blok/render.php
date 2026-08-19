<?php
defined('ABSPATH') || exit();

/**
 * Content blok — kop/tekst/knoppen (InnerBlocks) met een optionele afbeelding
 * en achtergrondkleur, bedoeld om BINNEN kolommen/containers te gebruiken
 * (core/columns, core/group) i.p.v. als eigen pagina-brede sectie zoals
 * acf/cta. Daarom geen "align"-support en geen sectie/kaart-scheiding zoals
 * cta's eigen fullWidth-truc: de root van dit block IS de kaart zelf.
 *
 * "backgroundColor" is bewust een EIGEN attribute + een eigen "Kleur"-paneel
 * (editor.js, PanelColorSettings), niet het native "Kleuren"-paneel (dat WEL
 * nog "text" heeft, zie supports.color hieronder) — de native background-/
 * gradient-controls staan onder een APARTE "Stijlen"-tab in de zijbalk, los
 * van de "Blok"-tab waar de rest van dit block's instellingen (Layout,
 * Afbeelding) staan. Twee controls die dezelfde achtergrond zouden zetten was
 * de eerste, verworpen optie — dit paneel is de vervanging, niet een
 * aanvulling: supports.color.background/gradients staan hierboven bewust op
 * false. Zelfde patroon als acf/hero's paginatie-/navigatieknoppenkleur, hier
 * toegepast puur voor zichtbaarheid/gemak i.p.v. omdat de kaart een apart
 * sub-element is (dat is cta's eigen, andere reden voor cardBackgroundColor).
 *
 * "direction" (verticaal/horizontaal) is een bewuste editor-keuze, geen
 * viewport-responsive gedrag — maar de horizontale variant schakelt zelf WEL
 * terug naar verticaal op een smalle CONTAINER (@container/@lg:, Tailwind
 * v4's eigen container-query-utilities, drempel 32rem), niet op een smalle
 * VIEWPORT (lg:) zoals acf/cta's eigen side-layout. Een viewport-breakpoint
 * zou hier verkeerd zijn: dit block zit typisch in een kolom die op een breed
 * scherm nog steeds smal kan zijn (bv. een layout met 3-4 kolommen naast
 * elkaar) — @container kijkt naar de eigen beschikbare breedte van de kaart,
 * niet naar het scherm, en reageert dus correct ongeacht hoe smal de kolom
 * toevallig is waar dit block in geplaatst wordt.
 *
 * "imageAspect" geeft een echte keuze in beeldverhouding (vierkant/breed/
 * staand/origineel) i.p.v. acf/cta's eigen vaste aspect-square naast de
 * inhoud — "origineel" laat de afbeelding op zijn natuurlijke verhouding
 * staan (geen aspect-ratio-class, geen object-cover), de overige drie
 * croppen met object-cover naar een vaste verhouding.
 *
 * "imageFullWidth" is alleen relevant/getoond bij "vertical" (afbeelding
 * boven de inhoud): de afbeelding loopt dan tot de randen van DIT block
 * (geen padding, geen eigen rounded-xl) — een ander, kleiner concept dan
 * acf/cta's eigen "fullWidth", die tot de randen van het SCHERM loopt. De
 * kaart krijgt in dat geval "overflow-hidden" zodat de afbeelding automatisch
 * de kaart's eigen rounded-2xl-hoeken volgt i.p.v. zelf een aparte
 * rounded-t-2xl te moeten bijhouden — zelfde truc als acf/cta's eigen
 * achtergrondafbeelding.
 *
 * De inhoud zelf (kop, tekst, knoppen) is InnerBlocks, geen veld — zie
 * acf/hero's eigen docblock voor de volle uitleg van SCF's
 * <InnerBlocks />-vervang-truc.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter of "!": wptexturize() zet een cijfer direct gevolgd
 * door een aanhalingsteken om in een dubbele-prime-entity, wat het sluitende
 * aanhalingsteken kapotmaakt. "aspect-3/4" (portrait) eindigt zelf op een
 * cijfer — de img-classes hieronder gebruiken daarom array_filter()+implode()
 * met "m-0!" altijd als laatste, gegarandeerd aanwezige element, i.p.v. een
 * handmatige volgorde die per keuze kan verschuiven.
 */

$direction = ($block['direction'] ?? 'vertical') === 'horizontal' ? 'horizontal' : 'vertical';
$image_position = ($block['imagePosition'] ?? 'right') === 'left' ? 'left' : 'right';
$image_aspect = $block['imageAspect'] ?? 'square';
$image_full_width = $direction === 'vertical' && !empty($block['imageFullWidth']);

// ownframework_resolve_block_image() — inc/functions/image-helpers.php,
// gedeeld met acf/cta.
$image = ownframework_resolve_block_image($block['image'] ?? []);
$has_image = $image['url'] !== '';

$aspect_class = match ($image_aspect) {
    'video' => 'aspect-video',
    'portrait' => 'aspect-3/4',
    'auto' => '',
    default => 'aspect-square',
};
// "auto" laat de afbeelding op zijn natuurlijke verhouding staan — croppen
// met object-cover heeft dan niets om naartoe te croppen.
$image_fit_class = $image_aspect === 'auto' ? '' : 'object-cover';

$inset_image_class = implode(' ', array_filter(['w-full', 'rounded-xl', $image_fit_class, $aspect_class, 'm-0!']));
$bleed_image_class = implode(' ', array_filter(['w-full', $image_fit_class, $aspect_class, 'm-0!']));

$is_horizontal = $direction === 'horizontal' && $has_image;
$is_bleed = !$is_horizontal && $has_image && $image_full_width;

// "@container" hoort HIER op de root, niet los op de flex-rij hieronder:
// @lg:flex-row/@lg:w-2/5 kunnen alleen matchen tegen een voorouder die zelf
// container-type declareert — zonder deze class op een ancestor query een
// @container-variant tegen niets, en matcht hij per spec dus nooit.
// "own-surface" (app.css), niet "shadow-sm": dezelfde rand-zonder-schaduw als
// acf/zijbalk-navigatie/acf/vacatures/acf/agenda's eigen kaarten — een inline
// kaart in de pagina-flow krijgt in dit thema alleen een rand, geen shadow.
$card_class = trim('own-content-blok own-surface relative @container rounded-2xl ' . ($is_bleed ? 'overflow-hidden' : ''));

// Al resolved naar een "var(--wp--preset--color--<slug>)" (of een losse hex-
// waarde) door editor.js's eigen resolveToPresetVar() — hier gewoon direct
// bruikbaar, geen "var(...)"-wrapping meer nodig zoals bij een native
// backgroundColor-slug.
$bg_value = $block['backgroundColor'] ?: '';

$card_style_parts = [];
if ($bg_value !== '') {
    $card_style_parts[] = 'background-color:' . esc_attr($bg_value);
}
// Altijd als laatste, nooit conditioneel: garandeert dat de style-attribute
// nooit op een cijfer eindigt (zie de docblock hierboven) — het native
// "Kleuren"-paneel voegt hier verder niets meer aan toe (background/gradients
// staan uit in block.json), maar de gekozen kleur hierboven kan zelf op een
// cijfer eindigen (bv. "#062b20"), dus de regel blijft nodig.
$card_style_parts[] = 'box-sizing:border-box';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $card_class,
    'style' => implode(';', $card_style_parts),
]);

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg.
 */
$content_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":2,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Beschrijving…"}],["core/buttons",{}]]\' allowedBlocks=\'["core/heading","core/paragraph","core/buttons","core/list"]\' templateLock=\'false\' />';
?>
<div <?= $wrapper_attributes; ?>>
    <?php if ($is_horizontal) : ?>
        <div class="flex flex-col @lg:flex-row gap-6 @lg:gap-8 p-4 sm:p-6 lg:p-8 items-center">
            <div class="<?= $image_position === 'left' ? 'order-1' : 'order-2'; ?> shrink-0 w-full @lg:w-2/5">
                <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="<?= esc_attr($inset_image_class); ?>">
            </div>
            <div class="<?= $image_position === 'left' ? 'order-2' : 'order-1'; ?> w-full">
                <div class="prose max-w-none">
                    <?= $content_inner_blocks; ?>
                </div>
            </div>
        </div>
    <?php elseif ($is_bleed) : ?>
        <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="<?= esc_attr($bleed_image_class); ?>">
        <div class="p-6 sm:p-8">
            <div class="prose max-w-none">
                <?= $content_inner_blocks; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="flex flex-col gap-6 p-6 sm:p-8">
            <?php if ($has_image) : ?>
                <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" class="<?= esc_attr($inset_image_class); ?>">
            <?php endif; ?>
            <div class="prose max-w-none">
                <?= $content_inner_blocks; ?>
            </div>
        </div>
    <?php endif; ?>
</div>