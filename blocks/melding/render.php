<?php
defined('ABSPATH') || exit();

/**
 * Melding: icoon + bewerkbare inhoud (InnerBlocks) + optionele sluitknop.
 *
 * "variant" levert alleen standaardkleuren en het standaardicoon (via
 * .own-melding--<variant> in src/css/components/melding.css); elk van de vier
 * eigen kleurattributen overschrijft er één, als inline custom property op
 * de wrapper. Bij "aangepast" levert "customColor" (--own-melding-base) de
 * basiskleur waar melding.css de overige tinten zelf van afleidt; leeg = de
 * primary-kleur uit Ontwerp. Eigen attributen i.p.v. supports.color: het
 * native "Kleuren"-paneel kan icoon/titel/tekst niet los kleuren (CLAUDE.md
 * Traps #7).
 *
 * Wegklikken loopt via Alpine.data('ownMelding') (src/js/scripts/melding.js).
 * Met "rememberDismiss" onthoudt localStorage het wegklikken; de sleutel is
 * een hash van de inhoud, zodat een aangepaste melding weer verschijnt.
 * In de editor draait Alpine niet, dus daar blijft de melding altijd staan.
 */

$variants = ['info', 'succes', 'waarschuwing', 'fout', 'aangepast'];
$variant = in_array($block['variant'] ?? '', $variants, true) ? $block['variant'] : 'info';
$appearance = ($block['appearance'] ?? 'rounded') === 'accent' ? 'accent' : 'rounded';

$icon = $block['icon'] ?? 'auto';
if ($icon === 'auto') {
    $icon = $variant === 'aangepast' ? 'info' : $variant;
}
$icon_html = $icon !== 'none' ? ownframework_get_icon('melding-' . $icon, 'own-melding__icon') : '';

$dismissible = !empty($block['dismissible']);
$storage_key = $dismissible && !empty($block['rememberDismiss'])
    ? 'own-melding-' . substr(md5((string) $content), 0, 12)
    : '';

$color_vars = [
    'noticeBackgroundColor' => '--own-melding-bg',
    'noticeAccentColor' => '--own-melding-accent',
    'noticeTitleColor' => '--own-melding-title',
    'noticeTextColor' => '--own-melding-text',
];
$style_parts = [];
if ($variant === 'aangepast' && !empty($block['customColor'])) {
    $style_parts[] = '--own-melding-base:' . esc_attr($block['customColor']);
}
foreach ($color_vars as $attribute => $property) {
    if (!empty($block[$attribute])) {
        $style_parts[] = $property . ':' . esc_attr($block[$attribute]);
    }
}

$wrapper_extra = [
    'class' => "own-melding own-melding--{$variant} own-melding--{$appearance}",
    'style' => implode(';', $style_parts),
    'role' => $variant === 'fout' || $variant === 'waarschuwing' ? 'alert' : 'status',
];
$wrapper_attributes = get_block_wrapper_attributes($wrapper_extra);

$inner_blocks = '<InnerBlocks '
    . 'template=\'[["core/heading",{"level":3,"placeholder":"Titel van de melding","className":"own-melding__title"}],["core/paragraph",{"placeholder":"Schrijf hier de tekst van de melding."}]]\' '
    . 'allowedBlocks=\'["core/heading","core/paragraph","core/list","core/buttons"]\' '
    . 'templateLock=\'false\' '
    . 'className=\'own-melding__content\' />';
?>
<div <?= $wrapper_attributes; ?><?php if ($dismissible) : ?> x-data="<?= esc_attr("ownMelding('" . $storage_key . "')"); ?>" x-show="open"<?php endif; ?>>
    <?php if ($icon_html !== '') : ?>
        <div class="own-melding__icon-wrap"><?= $icon_html; ?></div>
    <?php endif; ?>

    <?= $inner_blocks; ?>

    <?php if ($dismissible) : ?>
        <button type="button" class="own-melding__close" @click="dismiss()">
            <span class="sr-only"><?php esc_html_e('Melding sluiten', 'own-framework'); ?></span>
            <?= ownframework_get_icon('melding-sluiten', 'own-melding__close-icon'); ?>
        </button>
    <?php endif; ?>
</div>
