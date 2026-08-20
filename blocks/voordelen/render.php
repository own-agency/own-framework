<?php
defined('ABSPATH') || exit();

/**
 * Voordelen — titel plus een raster van voordelen/USP's, met acf/voordeel als
 * geneste kinderen: zelfde InnerBlocks-nestpatroon als acf/proces nest acf/
 * proces-stap (zie dat block's eigen docblock voor de volle uitleg van SCF's
 * <InnerBlocks />-vervang-truc). Dit block regelt alleen de omlijsting
 * (titel, kolommen); elke voordeel-kaart is een los, onafhankelijk kind dat
 * niets van zijn buren hoeft te weten.
 *
 * HET GENUINE VERSCHIL met acf/proces: geen enkele eigen kleur-attribute
 * hier (acf/proces heeft er drie, voor het rondje/nummer/lijn — CLAUDE.md
 * Traps #7). Elke voordeel's icoon is een echt genest "core/icon"-block
 * (WordPress 7.1's eigen Icoon-blok, met zijn NATIVE achtergrond-/tekst-/
 * randkleur- en paddingpanelen) — de kleur van dat icoon-"badge" is dus al
 * per instantie in te stellen via dat block's eigen zijbalk, geen tweede,
 * losstaande kleurmechaniek hier nodig. Dat is ook precies waarom dit geen
 * losse "Kleur icoon"-paneel op DIT block heeft zoals acf/proces dat wel
 * moest bouwen: proces-stap se rondje/nummer/lijn zijn PUUR CSS-decoratie
 * zonder eigen block-identiteit, terwijl core/icon een echt, al styleable
 * block is — er is dus niets meer voor dit block om namens dat kind te
 * regelen.
 *
 * "columns" (editor.js) is een simpele 2/3-keuze voor het raster — geen
 * per-stap kolomindeling of iets geavanceerders, dezelfde "hou het simpel
 * tenzij nodig"-lijn als de rest van dit theme.
 *
 * Elke nieuwe InnerBlocks-template-instantie (acf/voordeel) krijgt zijn
 * eigen, voorgestelde icoon-styling (achtergrond "Primair", icoonkleur
 * "Basis", afgeronde hoeken, wat padding — zie blocks/voordeel/render.php's
 * eigen docblock) zodat een net ingevoegd voordeel er al verzorgd uitziet
 * zonder dat een editor eerst zelf kleuren moet kiezen. Een editor die een
 * ANDERE kleur wil voor één specifiek icoon past 'm gewoon aan via dat
 * icoon's eigen kleurenpaneel — de eenvoudigste manier om een NIEUW voordeel
 * dat er al hetzelfde uitziet toe te voegen, is het dupliceren van een
 * bestaand voordeel (kopieert alle geneste attributes mee), niet een gloednieuw
 * "acf/voordeel" invoegen (dat begint weer bij de template-defaults hierboven).
 */

$title = trim($block['title'] ?? '');
$columns = ($block['columns'] ?? '2') === '3' ? '3' : '2';

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
// zelfde reden als acf/proces/acf/feitjes-slider (zie CLAUDE.md "Alignment,
// width and full-bleed backgrounds").
$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
$style_parts[] = 'box-sizing:border-box';

// get_block_wrapper_attributes() vertaalt de align-attribute bij een
// SCF-block met "mode": "preview" niet betrouwbaar naar een
// "alignwide"-klasse op de front-end — die dus zelf toevoegen, zelfde
// aanpak als acf/proces/acf/afbeeldingen-slider/acf/hero.
$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('own-voordelen relative ' . $align_class),
    'style' => implode(';', $style_parts),
]);

$grid_columns_class = $columns === '3' ? 'sm:grid-cols-3' : 'sm:grid-cols-2';
$item_count = $columns === '3' ? 3 : 4;
$item_template = implode(',', array_fill(0, $item_count, '["acf/voordeel",{}]'));

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg. "own-voordelen-list" is de className die
 * SCF's eigen "acf-innerblocks-container" vervangt (CLAUDE.md Traps #13) —
 * de daadwerkelijke grid-layout-hook, front-end én editor.
 */
$items_inner_blocks = '<InnerBlocks className=\'own-voordelen-list grid grid-cols-1 ' . $grid_columns_class . ' gap-x-12 gap-y-10\' template=\'[' . $item_template . ']\' allowedBlocks=\'["acf/voordeel"]\' templateLock=\'false\' />';
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php // Elke margin-utility hier met een eigen "!": deze <h2> zit als
        // afstammeling van "core/post-content"'s "prose"-className (CLAUDE.md
        // Traps #3) ook onder ".prose h2"'s eigen margin-regel, die anders
        // (zonder "!important") deze losse mb-*-utilities weer overschrijft —
        // zelfde aanpak als acf/proces/render.php's eigen titel.
        ?>
        <?php if ($title !== '') : ?>
            <h2 class="mb-8! sm:mb-10! lg:mb-12! mt-0!"><?= esc_html($title); ?></h2>
        <?php elseif (!empty($is_preview)) : ?>
            <h2 class="opacity-50 mb-8! sm:mb-10! lg:mb-12! mt-0!"><?php esc_html_e('Titel…', 'own-framework'); ?></h2>
        <?php endif; ?>
        <?= $items_inner_blocks; ?>
    </div>
</section>
