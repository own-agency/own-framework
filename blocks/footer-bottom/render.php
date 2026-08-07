<?php
defined('ABSPATH') || exit();

/**
 * Footer onderbalk: copyright-tekst + "Copyright Menu" (cookie-/privacybeleid
 * e.d.) + de vaste "Made with 💙 by OWN agency"-regel — nooit instelbaar,
 * stond ook in de vorige, klassieke versie van dit thema al hardcoded.
 *
 * <div> als root, niet <footer>: acf/footer (ervoor of erna in parts/
 * footer.html) draagt het echte <footer>-landmark, zelfde asymmetrie als
 * acf/header-topbar (<div>) vs acf/header (<header>) — zie dat block's eigen
 * render.php-comment voor de volledige redenering.
 *
 * "© {jaar} {sitenaam}" blijft hardcoded PHP (get_bloginfo/gmdate) i.p.v. in
 * een InnerBlocks-tekst, zodat het jaartal elk jaar automatisch meeloopt —
 * een editor die per ongeluk "© 2024" laat staan is precies het probleem dat
 * dit voorkomt.
 *
 * Responsive in 3 stappen, niet 2: onder sm staat de copyright-tekst boven
 * het copyright-menu (own-footer-bottom-copyright's eigen flex-col) — samen
 * op één regel proberen te persen naast een lang copyright-menu (Algemene
 * voorwaarden/Privacyverklaring/Cookiebeleid...) is precies waar het als
 * eerste knelt. Vanaf sm staan tekst/menu weer naast elkaar (sm:flex-row),
 * met de scheidingsstreep ertussen (footer.css's own-footer-bottom-menu
 * ::before) pas vanaf diezelfde breakpoint zichtbaar — een streep tussen
 * twee dingen die op mobiel juist onder elkaar staan, niet naast elkaar,
 * zou niets meer scheiden. Vanaf md staat de "Made by"-regel weer naast
 * (niet onder) dat hele blok (md:flex-row op de buitenste container); flex-
 * wrap daar ook op die buitenste rij, voor als het copyright-blok zelf op
 * een middelgroot scherm nog te breed is om naast "Made by" te passen.
 */

if (!empty($block['style']['color']['background'])) {
	$bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
	$bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
	$bg_value = 'var(--wp--preset--color--base)';
}

if (!empty($block['style']['color']['text'])) {
	$text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
	$text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
	// "contrast", not "inherit": same reasoning as acf/footer's own render.php
	// — a readable default has to hold on its own, not depend on whatever
	// text colour happens to be inherited from further up the page.
	$text_value = 'var(--wp--preset--color--contrast)';
}

$style = sprintf('--own-footer-bottom-bg:%s;--own-footer-bottom-text:%s;', esc_attr($bg_value), esc_attr($text_value));

$wrapper_attributes = get_block_wrapper_attributes([
	'class' => 'own-footer-bottom not-prose',
	'style' => $style,
]);
?>
<div <?= $wrapper_attributes; ?>>
	<div class="container flex flex-col flex-wrap gap-2 py-4 text-xs md:flex-row md:items-center md:justify-between md:gap-x-4 md:gap-y-2">
		<div class="own-footer-bottom-copyright flex flex-col items-start gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3">
			<p>&copy; <?= esc_html(gmdate('Y')); ?> <?= esc_html(get_bloginfo('name')); ?></p>

			<?php if (has_nav_menu('copyright-menu')) : ?>
				<?php
				wp_nav_menu([
					'theme_location' => 'copyright-menu',
					'container' => false,
					'fallback_cb' => false,
					'depth' => 1,
					'menu_class' => 'own-footer-bottom-menu flex flex-wrap items-center',
					'li_class' => 'own-footer-bottom-menu__item hover:underline!',
				]);
				?>
			<?php endif; ?>
		</div>

		<p><?= esc_html__('Made by', 'own-framework'); ?> <a href="https://ownagency.nl/" class="hover:underline!" target="_blank" rel="noopener">OWN agency</a></p>
	</div>
</div>