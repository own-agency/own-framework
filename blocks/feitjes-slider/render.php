<?php
defined('ABSPATH') || exit();

/**
 * Feitjes slider — dezelfde opzet als acf/afbeeldingen-slider's "Standaard"-
 * variant en acf/content-blokken-slider (titel + ronde vorige/volgende-knoppen
 * op één rij, optionele paginatie-bolletjes), maar met kerncijfers als slides:
 * per feitje een groot getal ("200+") met een kort label eronder
 * ("leerlingen").
 *
 * De feitjes zijn gewone block-attributes (een "facts"-array van
 * {value, label}), GEEN geneste blocks zoals bij acf/content-blokken-slider.
 * Een feitje is puur data — twee korte strings zonder eigen opmaakkeuzes —
 * en gedraagt zich daarmee als een afbeelding in acf/afbeeldingen-slider,
 * niet als een content-kaart. Dat scheelt niet alleen een tweede
 * blockregistratie, het maakt ook de editor-preview eerlijker: omdat er niets
 * bewerkbaars ín een slide zit, kan hier — anders dan bij acf/content-blokken-
 * slider — gewoon dezelfde Swiper-markup in de editor als op de front-end,
 * en draait src/js/scripts/facts-slider.js dus ook in src/js/editor-scripts.js.
 *
 * "valueColor"/"labelColor" zijn eigen attributes + een eigen "Kleur"-paneel
 * (editor.js, PanelColorSettings) i.p.v. het native "Kleuren"-paneel: dat
 * laatste kleurt via get_block_wrapper_attributes() altijd de hele sectie
 * (achtergrond + ALLE tekst) in één keer, terwijl het getal en het label hier
 * juist los van elkaar te kleuren moeten zijn. Zelfde reden als acf/hero's
 * paginatie-/navigatieknoppenkleur.
 *
 * Alleen "valueColor" heeft een eigen default (de primaire kleur — een
 * kerncijfer is het element dat juist wél op moet vallen). "labelColor" blijft
 * standaard leeg en erft dus gewoon de tekstkleur van de sectie, zodat het
 * label vanzelf meekleurt met een donkere/lichte achtergrond zonder dat daar
 * per instance een keuze voor gemaakt hoeft te worden. Zodra er wél een
 * labelkleur gekozen is vervalt de "opacity-80" die dat label anders subtieler
 * dan het getal maakt: die dempte dan een kleur die de editor nu net expliciet
 * heeft uitgekozen.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter of "!": wptexturize() zet een cijfer direct gevolgd
 * door een aanhalingsteken om in een dubbele-prime-entity, wat het sluitende
 * aanhalingsteken kapotmaakt. Class-lijsten hieronder zijn daarom zo geordend
 * dat ze altijd op een letter (of "!") eindigen, nooit op een cijfer —
 * "text-6xl" e.d. staan dus nooit achteraan. Om dezelfde reden eindigt elke
 * style-attribute hieronder op een ";": een door de editor gekozen hexkleur
 * (bv. "#062b20") eindigt zelf op een cijfer.
 */

$title = trim($block['title'] ?? '');
$facts = $block['facts'] ?? [];
$show_pagination = !empty($block['showPagination']);
$nav_color = $block['navColor'] ?: 'var(--wp--preset--color--primary)';
$pagination_color = $block['paginationColor'] ?: 'var(--wp--preset--color--primary)';
$value_color = $block['valueColor'] ?: 'var(--wp--preset--color--primary)';
$label_color = $block['labelColor'] ?: '';

// Zonder eigen labelkleur geen inline color: het label erft dan de tekstkleur
// van de sectie (zie de docblock hierboven). Trailing ";" om dezelfde reden
// als overal in dit bestand — een hexkleur eindigt zelf op een cijfer.
$label_style = $label_color !== ''
    ? ' style="color:' . esc_attr($label_color) . ';"'
    : '';

// "block" staat bewust achteraan, en "opacity-80" er dus nooit: een
// class-attribute mag niet op een cijfer eindigen (zie de docblock hierboven).
$label_class = 'own-feitje-label mt-2 text-base sm:text-lg '
    . ($label_color === '' ? 'opacity-80 ' : '')
    . 'block';

// Alleen 2/3/4/5 zijn geldige keuzes (editor.js); alles anders valt terug op
// de block.json-default i.p.v. een willekeurig getal aan Swiper te geven.
$slides_per_view = (int) ($block['slidesPerView'] ?? 4);
if (!in_array($slides_per_view, [2, 3, 4, 5], true)) {
    $slides_per_view = 4;
}

// Een feitje zonder ingevuld getal én zonder label rendert een lege kolom —
// die eruit filteren i.p.v. 'm mee te tellen als slide. array_values() houdt
// de sleutels aaneengesloten, zodat het een echte lijst blijft.
$facts = array_values(array_filter($facts, function ($fact) {
    return trim((string) ($fact['value'] ?? '')) !== ''
        || trim((string) ($fact['label'] ?? '')) !== '';
}));

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
    'class' => trim('own-facts-slider relative ' . $align_class),
    'style' => implode(';', $style_parts),
]);
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php // flex-col op mobiel: een lange titel plus de twee ronde knoppen op één
        // rij verdrukken elkaar op een smal scherm — vanaf "sm" is er ruimte genoeg.
        // Zelfde koprij als acf/afbeeldingen-slider en acf/content-blokken-slider.
        ?>
        <div class="gap-4 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <?php if ($title !== '') : ?>
                <h2 class="mt-0! mb-0!"><?= esc_html($title); ?></h2>
            <?php elseif (!empty($is_preview)) : ?>
                <h2 class="opacity-50 mt-0! mb-0!"><?php esc_html_e('Titel…', 'own-framework'); ?></h2>
            <?php endif; ?>
            <div class="gap-2 shrink-0 [&_.swiper-button-disabled]:opacity-50 flex items-center select-none">
                <div role="button" tabindex="0" class="own-facts-slider-prev flex w-9 h-9 sm:w-10 sm:h-10 rounded-full cursor-pointer" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Vorige', 'own-framework'); ?>">
                    <svg class="w-4 h-4 m-auto text-white rotate-180 sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
                <div role="button" tabindex="0" class="own-facts-slider-next flex w-9 h-9 sm:w-10 sm:h-10 rounded-full cursor-pointer" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Volgende', 'own-framework'); ?>">
                    <svg class="w-4 h-4 m-auto text-white sm:w-5 sm:h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
            </div>
        </div>

        <?php if (empty($facts)) : ?>
            <p class="mb-0 text-sm text-center"><?php esc_html_e('Er zijn nog geen feitjes toegevoegd.', 'own-framework'); ?></p>
        <?php else : ?>
            <div class="swiper overflow-visible! select-none" data-own-facts-slider="<?= esc_attr((string) $slides_per_view); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($facts as $fact) :
                        $value = trim((string) ($fact['value'] ?? ''));
                        $label = trim((string) ($fact['label'] ?? '')); ?>
                        <div class="swiper-slide h-auto!">
                            <?php // "text-center" achteraan, nooit een py-*: een class-attribute
                            // mag niet op een cijfer eindigen (zie de docblock hierboven).
                            ?>
                            <div class="own-feitje py-6 sm:py-10 text-center">
                                <?php if ($value !== '') : ?>
                                    <?php // Geen <h2>/<h3>: dit is een kerncijfer, geen kop in de
                                    // documentstructuur — een schermlezer die de koppenlijst
                                    // doorloopt heeft niets aan een reeks losse getallen. De
                                    // "grote kop"-look komt puur uit de classes hieronder.
                                    ?>
                                    <span class="own-feitje-value font-headings text-4xl sm:text-5xl lg:text-6xl font-bold block leading-none" style="color:<?= esc_attr($value_color); ?>;"><?= esc_html($value); ?></span>
                                <?php endif; ?>
                                <?php if ($label !== '') : ?>
                                    <span class="<?= esc_attr($label_class); ?>"<?= $label_style; ?>><?= esc_html($label); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-facts-slider-pagination mt-2 w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
