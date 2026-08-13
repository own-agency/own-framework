<?php
defined('ABSPATH') || exit();

/**
 * Proces — genummerde stappen (bv. een aanvraagproces), met acf/proces-stap
 * als geneste kinderen: zelfde InnerBlocks-nestpatroon als acf/footer nest
 * -cta/-menu/-contact, en acf/content-blokken-slider nest acf/content-blok.
 * Elke stap houdt zijn eigen bewerkbare kop/tekst (InnerBlocks); dit block
 * regelt alleen de omlijsting (titel, kleuren, de stappenrij).
 *
 * Anders dan acf/content-blokken-slider (Traps #13) is hier GEEN Swiper die
 * elk kind in een eigen wrapper moet krijgen — acf/proces-stap rendert zelf al
 * zijn eigen root-element. Front-end en editor kunnen daardoor exact dezelfde
 * <InnerBlocks />-placeholdertekst gebruiken (zie acf/hero's eigen docblock:
 * SCF vervangt die automatisch door de daadwerkelijke inhoud, front-end én
 * editor-preview) — geen $wp_block->inner_blocks-loop nodig zoals bij de
 * sliders. SCF's eigen vervanging wrapt de kinderen op de front-end zelf nog
 * in één <div class="own-proces-list">
 * (includes/blocks.php:acf_replace_inner_blocks_in_block_content(), leest de
 * "className" hieronder) — vandaar géén <ol>/<li> maar plain <div>'s; een
 * <li> binnen die <div> zou ongeldige markup zijn.
 *
 * Nummering, het vinkje op de laatste stap en het verbindingslijntje tussen
 * stappen zijn bewust PURE CSS (src/css/components/proces.css): een NAMED CSS
 * counter (niet de browser's ingebouwde lijst-nummering, want er is geen echte
 * <ol>) telt elke ".own-proces-stap" binnen ".own-proces-list", en
 * ":last-child" weet vanzelf welke stap de laatste is — dat werkt op een
 * gewone <div> precies zo goed als op een <li>. Geen PHP-telling
 * ($count/$isLast) nodig zoals in de aangeleverde referentie-implementatie
 * (die was flexible-content-PHP, één bestand voor alle stappen samen) — hier
 * is elke stap een los, onafhankelijk gerenderd block dat niets van zijn
 * buren hoeft te weten.
 *
 * "stepColor"/"stepNumberColor"/"stepLineColor" zijn eigen attributes + een
 * eigen "Kleur stappen"-paneel (editor.js, PanelColorSettings), niet het
 * native "Kleuren"-paneel — dat kleurt via get_block_wrapper_attributes()
 * altijd de hele sectie, terwijl deze drie specifiek het rondje/de lijn van
 * elke stap raken (CLAUDE.md Traps #7). Ze gaan als CSS custom properties op
 * de wrapper en worden door acf/proces-stap's eigen CSS overgenomen via
 * gewone CSS-overerving (geen block context nodig) — zelfde patroon als
 * --own-pagination-color bij de slider-blocks.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter of "!": wptexturize() zet een cijfer direct gevolgd
 * door een aanhalingsteken om in een dubbele-prime-entity, wat het sluitende
 * aanhalingsteken kapotmaakt. Class-lijsten hieronder zijn daarom zo geordend
 * dat ze altijd op een letter eindigen, nooit op een cijfer.
 */

$title = trim($block['title'] ?? '');

$step_color = $block['stepColor'] ?: 'var(--wp--preset--color--primary)';
// Geen eigen default: valt terug op wit, het vaakst leesbare nummer/vinkje
// boven op een gekleurd rondje, zonder dat een editor die niets kiest daar
// zelf iets voor moet instellen.
$step_number_color = $block['stepNumberColor'] ?: '#fff';
// Zonder eigen lijnkleur volgt de lijn het rondje, precies zoals in de
// aangeleverde referentie-implementatie ($stepsLineColor ?: $stepsBgColor).
$step_line_color = $block['stepLineColor'] ?: $step_color;

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

// Geen background-color hier: die gaat naar het full-bleed laagje hieronder,
// zelfde reden als acf/feitjes-slider/acf/content-blokken-slider (zie
// CLAUDE.md "Alignment, width and full-bleed backgrounds").
$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
$style_parts[] = '--own-proces-step-color:' . esc_attr($step_color);
$style_parts[] = '--own-proces-step-number-color:' . esc_attr($step_number_color);
$style_parts[] = '--own-proces-step-line-color:' . esc_attr($step_line_color);
// Altijd als laatste, nooit conditioneel: garandeert dat de style-attribute
// nooit op een cijfer eindigt (zie de docblock hierboven) — een gekozen
// hexkleur hierboven (bv. "#062b20") eindigt zelf op een cijfer.
$style_parts[] = 'box-sizing:border-box';

// get_block_wrapper_attributes() vertaalt de align-attribute bij een
// SCF-block met "mode": "preview" niet betrouwbaar naar een
// "alignwide"-klasse op de front-end — die dus zelf toevoegen, zelfde
// aanpak als acf/afbeeldingen-slider en acf/hero.
$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('own-proces relative ' . $align_class),
    'style' => implode(';', $style_parts),
]);

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg.
 */
$steps_inner_blocks = '<InnerBlocks className=\'own-proces-list\' template=\'[["acf/proces-stap",{}],["acf/proces-stap",{}],["acf/proces-stap",{}]]\' allowedBlocks=\'["acf/proces-stap"]\' templateLock=\'false\' />';
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php // Elke margin-utility hier met een eigen "!": deze <h2> zit als
        // afstammeling van "core/post-content"'s "prose"-className (CLAUDE.md
        // Traps #3) ook onder ".prose h2"'s eigen margin-regel, die anders
        // (zonder "!important") deze losse mb-*-utilities weer overschrijft —
        // zelfde soort cascade-conflict als Traps #19's "m-0!" op elke <img>,
        // hier alleen op margin-bottom i.p.v. margin. "mt-0!" staat om die
        // reden ook altijd, niet alleen als laatste veilige token (zie
        // hieronder), en moet zelf ook al op een letter/"!" eindigen, nooit op
        // een cijfer.
        ?>
        <?php if ($title !== '') : ?>
            <h2 class="mb-8! sm:mb-10! lg:mb-12! mt-0!"><?= esc_html($title); ?></h2>
        <?php elseif (!empty($is_preview)) : ?>
            <h2 class="opacity-50 mb-8! sm:mb-10! lg:mb-12! mt-0!"><?php esc_html_e('Titel…', 'own-framework'); ?></h2>
        <?php endif; ?>
        <nav aria-label="<?php esc_attr_e('Voortgang', 'own-framework'); ?>">
            <?= $steps_inner_blocks; ?>
        </nav>
    </div>
</section>
