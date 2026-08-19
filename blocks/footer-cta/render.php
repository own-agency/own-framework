<?php
defined('ABSPATH') || exit();

/**
 * Footer CTA-kaart — een eigen, genest block in plaats van een stuk van acf/
 * footer's eigen render.php: één SCF-block ondersteunt maar één InnerBlocks-
 * gebied (zie acf/footer's eigen render.php-comment), dus dit MOET een echt
 * geregistreerd block zijn om als kind van core/column te kunnen bestaan,
 * naast core/site-logo/core/paragraph in dezelfde kolom. "ancestor":
 * ["acf/footer"] (block.json) houdt 'm buiten de inserter elders — deze
 * kaart erft --own-footer-text via de CSS-cascade van acf/footer's eigen
 * wrapper en heeft buiten die context geen zin.
 *
 * Geen eigen "toon kaart"-schakelaar (meer): dit IS nu een los, verwijder-
 * baar block ("lock":{"remove":false} op zijn template-item in acf/footer's
 * render.php overschrijft de rest van die InnerBlocks-boom z'n contentOnly-
 * lock specifiek voor dit block) — een editor die de kaart niet wil, haalt
 * 'm er gewoon uit i.p.v. een schakelaar om te zetten. Titel/link moeten
 * nog steeds allebei gevuld zijn voor de kaart op de live site verschijnt
 * (nooit een lege/kapotte kaart). In de editor (is_preview) blijft de kaart
 * ALTIJD zichtbaar, ook leeg — anders is er niets om op te klikken en de
 * tekstvelden te bereiken zodra 'm net (opnieuw) is toegevoegd. Op de live
 * site verdwijnt een lege kaart dan gewoon volledig.
 */

$cta_subtitle = trim($block['ctaSubtitle'] ?? '');
$cta_title = trim($block['ctaTitle'] ?? '');
$cta_url = trim($block['ctaUrl'] ?? '');
$show_cta = $cta_title !== '' && $cta_url !== '';

if (!$show_cta && empty($is_preview)) {
	return;
}

$cta_new_tab = !empty($block['ctaOpenInNewTab']);
// Pijl-icoon voor "blijft op deze site", externe-link-icoon (doos + pijl)
// voor "nieuw tabblad" — het icoon verandert dus mee met de toggle
// hieronder, niet los instelbaar.
$cta_icon = $cta_new_tab ? 'external-link' : 'arrow-right';

if (!empty($block['style']['color']['background'])) {
	$bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
	$bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
	$bg_value = 'var(--wp--preset--color--primary)';
}

if (!empty($block['style']['color']['text'])) {
	$text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
	$text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
	$text_value = '#fff';
}

// Inline style, niet een footer.css class-regel: WP's Ontwerp "Elements ▸
// Links"-kleur emit een .wp-elements-<hash> a:where(...)-regel die élke <a>
// raakt, met hogere specificiteit dan een gewone class — alleen een echte
// style="" op dit element zelf wint daar altijd tegen. Zelfde fix als acf/
// header's mega-menu-links en acf/footer-contact's links.
//
// "rounded-2xl": zelfde buitenste-kaart-radius als acf/zijbalk-navigatie/acf/
// vacatures/acf/agenda/acf/content-blok/acf/cta — was eerder "rounded-lg"
// zonder rand, een eigen, ongedocumenteerde 3e radius-stap naast de
// rounded-2xl/rounded-xl-conventie (CLAUDE.md's "Style settings"). GEEN
// "own-surface" (meer): deze kaart heeft ALTIJD een gevulde achtergrond (zie
// $bg_value's eigen default hierboven, valt terug op Primair) — die kleur
// geeft de kaart al genoeg afbakening tegen de pagina, een rand erbovenop
// oogt dan dubbelop, zelfde "geen rand zodra er een achtergrondkleur is"-
// regel als elke andere kaart met own-surface nu volgt (acf/content-blok,
// acf/cta).
$wrapper_attributes = get_block_wrapper_attributes([
	'class' => 'own-footer-cta flex items-center justify-between gap-4 rounded-xl p-6 lg:max-w-xs',
	'style' => sprintf('background-color:%s;color:%s;', esc_attr($bg_value), esc_attr($text_value)),
]);
?>
<a <?= $wrapper_attributes; ?> href="<?= esc_url($cta_url); ?>" <?php if ($cta_new_tab) : ?> target="_blank" rel="noopener" <?php endif; ?>>
	<span class="flex flex-col">
		<?php if ($cta_subtitle !== '') : ?>
			<span class="mb-1 text-xs"><?= esc_html($cta_subtitle); ?></span>
		<?php endif; ?>
		<span class="font-semibold"><?= esc_html($cta_title !== '' ? $cta_title : __('CTA-titel…', 'own-framework')); ?></span>
	</span>
	<?= ownframework_get_icon($cta_icon, 'own-footer-cta-icon w-5 h-5 shrink-0'); ?>
</a>