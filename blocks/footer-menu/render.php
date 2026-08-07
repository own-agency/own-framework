<?php
defined('ABSPATH') || exit();

/**
 * Footer-menu — een eigen, genest block in plaats van een stuk van acf/
 * footer's eigen render.php: zelfde reden als acf/footer-cta's eigen
 * render.php-comment (InnerBlocks staat maar 1 gebied per SCF-block toe).
 * Geen eigen kleursupport: menu-items erven --own-footer-text al via de
 * CSS-cascade van acf/footer's eigen wrapper, geen dubbele kleurkeuze nodig.
 *
 * menuId (editor.js's eigen menu-picker) laat een editor ELK bestaand menu
 * kiezen, niet alleen wat er via Weergave → Menu's aan de "Footer Menu
 * 1"-locatie hangt — 0 (default) betekent "nog geen keuze gemaakt", val dan
 * terug op die locatie. wp_get_nav_menu_object() ipv has_nav_menu(): een
 * DIRECT gekozen menu-ID hoeft niet aan een locatie te hangen.
 */

$menu_id = (int) ($block['menuId'] ?? 0);
$has_menu = $menu_id > 0 ? (bool) wp_get_nav_menu_object($menu_id) : has_nav_menu('menu-1');

if (!$has_menu && empty($is_preview)) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes();
$menu_args = [
	'container' => false,
	'fallback_cb' => false,
	'depth' => 1,
	'menu_class' => 'own-footer-menu flex flex-col gap-3 text-sm font-medium',
	'li_class' => 'hover:underline!',
];
$menu_args[$menu_id > 0 ? 'menu' : 'theme_location'] = $menu_id > 0 ? $menu_id : 'menu-1';
?>
<div <?= $wrapper_attributes; ?>>
	<?php if ($has_menu) : ?>
		<?php wp_nav_menu($menu_args); ?>
	<?php else : ?>
		<p class="text-sm opacity-60"><?php esc_html_e('Geen menu gekozen.', 'own-framework'); ?></p>
	<?php endif; ?>
</div>