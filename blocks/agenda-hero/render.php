<?php
defined('ABSPATH') || exit();

/**
 * Agenda header — titel en tekst op een effen kleur- of verloopachtergrond
 * (WordPress' eigen native "Kleuren"-paneel, block.json's "supports.color").
 * Losgetrokken van acf/agenda zelf (voorheen een kaal heading/paragraph-
 * groepje rechtstreeks in templates/archive-agenda.html) zodat de intro-
 * tekst en de resultatengrid onafhankelijk van elkaar te beheren/verplaatsen/
 * stylen zijn — zelfde reden als waarom acf/vacature-hero een apart block is
 * naast acf/vacatures. Geen zoekbalk (in tegenstelling tot acf/vacature-hero)
 * — een schoolagenda heeft geen zoekbehoefte die dat rechtvaardigt; filteren
 * gebeurt al op acf/agenda's eigen sidebar (categorie + periode).
 *
 * Met maar één te kleuren zone (de hele sectie) is er niets dat het native
 * "Kleuren"-paneel niet zelf al afdekt — geen eigen kleurattributen/-panelen
 * nodig zoals acf/agenda zelf die wel heeft (sub-elementen: badges,
 * checkboxes, kaarten). backgroundColor/textColor's eigen DEFAULTS
 * ("primary"/"base") geven een vers ingevoegd block meteen kleurcontrast,
 * i.p.v. de kale wit-op-wit van een default-loos block — pas achteraf zelf
 * aan te passen via Ontwerp, maar nooit een lege start.
 *
 * ".prose-invert" wordt toegepast op basis van de GEKOZEN tekstkleur (zelfde
 * ownframework_get_theme_json_colors()/ownframework_color_is_light()-paar
 * als acf/vacature-hero) — @tailwindcss/typography's eigen kop/tekst-kleuren
 * lopen via losse CSS-variabelen, niet via gewone `color`-inheritance, dus
 * zonder dit zou witte tekst op een donkere achtergrond alsnog donkere
 * koppen tonen.
 *
 * Alleen insertable binnen de agenda-archiefpagina (templates/
 * archive-agenda.html) — blocks/agenda-hero/hooks/
 * restrict-to-archive-agenda-template.php restrict ook acf/agenda zelf.
 */

$height = $block['height'] ?? 'auto';
$show_breadcrumbs = !empty($block['showBreadcrumbs']);
$content_align = ($block['contentAlign'] ?? 'center') === 'left' ? 'left' : 'center';

if (!empty($block['style']['color']['background'])) {
    $bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
    $bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
    $bg_value = '';
}

if (!empty($block['style']['color']['gradient'])) {
    $gradient_value = $block['style']['color']['gradient'];
} elseif (!empty($block['gradient'])) {
    $gradient_value = 'var(--wp--preset--gradient--' . $block['gradient'] . ')';
} else {
    $gradient_value = '';
}

$text_color_hex = '';
if (!empty($block['style']['color']['text'])) {
    $text_color_hex = $block['style']['color']['text'];
    $text_value = $text_color_hex;
} elseif (!empty($block['textColor'])) {
    $palette = ownframework_get_theme_json_colors();
    $text_color_hex = $palette[$block['textColor']]['color'] ?? '';
    $text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
    $text_value = '';
}

$use_prose_invert = $text_color_hex !== '' && ownframework_color_is_light($text_color_hex);

$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
$style_parts[] = 'box-sizing:border-box';

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';
$section_class = trim($align_class . ' relative own-agenda-hero');

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $section_class,
    'style' => implode(';', $style_parts),
]);

$bleed_class = ownframework_full_bleed_class();

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

$breadcrumbs = $show_breadcrumbs ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm', !empty($is_preview)) : '';

$hero_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":1,"placeholder":"Agenda"}],["core/paragraph",{"placeholder":"Een overzicht van belangrijke momenten door het jaar heen."}]]\' allowedBlocks=\'["core/heading","core/paragraph"]\' templateLock=\'false\' />';

$height_class = match ($height) {
    'medium' => 'min-h-[60vh] flex items-center py-12 sm:py-16 lg:py-20',
    'large' => 'min-h-[85vh] flex items-center py-12 sm:py-16 lg:py-20',
    'screen' => 'min-h-screen flex items-center',
    default => 'py-12 sm:py-16 lg:py-20',
};
?>
<section <?= $wrapper_attributes; ?>>
    <?php if (!empty($flat_bg_style_parts)) : ?>
        <div class="<?= $bleed_class; ?>" style="<?= esc_attr(implode(';', $flat_bg_style_parts)); ?>"></div>
    <?php endif; ?>

    <div class="<?= esc_attr(trim($height_class . ' own-agenda-hero-height')); ?>">
        <div class="relative z-10 max-w-2xl <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
            <?= $breadcrumbs; ?>
            <div class="prose <?= $use_prose_invert ? 'prose-invert' : ''; ?> max-w-none <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
                <?= $hero_inner_blocks; ?>
            </div>
        </div>
    </div>
</section>
