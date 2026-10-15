<?php
defined('ABSPATH') || exit();

/**
 * Zoekbalk die eruitziet als een zoekveld maar een <button> is: een klik
 * opent de bestaande zoekmodal van de header ($store.searchDialog, src/js/
 * scripts/live-search.js), zodat er maar één echte zoekervaring is. De modal
 * staat op elke pagina (blocks/header/render.php), dus dit block hoeft hem
 * niet zelf te renderen.
 *
 * x-data (leeg) is nodig: Alpine verwerkt @click alleen binnen een
 * x-data-scope, en de omliggende pagina heeft er hier geen.
 *
 * Kleuren zijn eigen attributen (CLAUDE.md Traps #7: veld en knop zijn twee
 * sub-elementen). Leeg = de standaard uit zoekbalk.css; de knop valt dan
 * terug op de site-brede Knop-stijl (.wp-element-button).
 *
 * In de editor ($is_preview) een <div> zonder Alpine: daar draait Alpine
 * niet en hoort een klik het block te selecteren, niet iets te openen.
 */

$placeholder = trim((string) ($block['placeholder'] ?? '')) ?: __('Waar ben je naar op zoek?', 'own-framework');
$button_text = trim((string) ($block['buttonText'] ?? '')) ?: __('Zoeken', 'own-framework');
$show_button = !empty($block['showButton']);
$size = ($block['size'] ?? 'normal') === 'large' ? 'large' : 'normal';

$color_vars = [
    'fieldBackgroundColor' => '--own-zoekbalk-bg',
    'fieldBorderColor' => '--own-zoekbalk-border',
    'fieldTextColor' => '--own-zoekbalk-text',
    'buttonBackgroundColor' => '--own-zoekbalk-button-bg',
    'buttonTextColor' => '--own-zoekbalk-button-text',
];
$style_parts = [];
foreach ($color_vars as $attribute => $property) {
    if (!empty($block[$attribute])) {
        $style_parts[] = $property . ':' . esc_attr($block[$attribute]);
    }
}

// Alleen bij een eigen knopkleur een modifier: zonder kleur moet de knop
// ongemoeid de Ontwerp-waarde van .wp-element-button houden.
$classes = ['own-zoekbalk', 'own-zoekbalk--' . $size];
if (!empty($block['buttonBackgroundColor'])) {
    $classes[] = 'own-zoekbalk--button-bg';
}
if (!empty($block['buttonTextColor'])) {
    $classes[] = 'own-zoekbalk--button-text';
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => implode(' ', $classes),
    'style' => implode(';', $style_parts),
]);
?>
<div <?= $wrapper_attributes; ?>>
    <?php if ($is_preview) : ?>
    <div class="own-zoekbalk__field">
    <?php else : ?>
    <button type="button" class="own-zoekbalk__field" x-data @click="$store.searchDialog.open()" aria-haspopup="dialog">
    <?php endif; ?>
        <?= ownframework_get_icon('search', 'own-zoekbalk__icon'); ?>
        <span class="own-zoekbalk__placeholder"><?= esc_html($placeholder); ?></span>
        <?php if ($show_button) : ?>
            <span class="own-zoekbalk__button wp-element-button"><?= esc_html($button_text); ?></span>
        <?php endif; ?>
    <?= $is_preview ? '</div>' : '</button>'; ?>
</div>
