<?php
defined('ABSPATH') || exit();

/**
 * Off-canvas mobielmenu voor blocks/header/render.php. Enige consument, dus
 * hier en niet in template-parts/. Drilldown-navigatie (elk item met
 * children krijgt zijn eigen paneel dat van rechts inschuift, op elke diepte)
 * via Ownframework_Mobile_Drilldown_Walker (blocks/header/hooks/) — geen
 * eigen x-data hier nodig: alles hangt aan mobileMenuOpen, al gedeclareerd op
 * <header> zelf (render.php), waar dit deel gewoon een kind van is.
 *
 * $args['cta_button']/$args['logo'] zijn de daadwerkelijk gerenderde HTML van
 * de desktop-header z'n eigen core/button en core/site-logo (render.php
 * haalt ze op via $wp_block->inner_blocks) — geen los "mobiel CTA"-veld en
 * geen eigen vaste logo-hoogte hier, dezelfde tekst/link/kleur resp. dezelfde
 * breedte-slider-grootte als in de balk erboven. Header-rij hieronder gebruikt
 * daarom ook bewust dezelfde py-2.5 als .own-header .container (header.css) —
 * niet een eigen waarde — zodat de rij zelf, niet alleen het logo erin, exact
 * even hoog is als de desktop-balk.
 *
 * position: fixed, geankerd op --wp-admin--admin-bar--height (WP core, 0px
 * als er geen admin-bar is) maar NIET ook op --site-header-height zoals de
 * vorige, simpele mobielmenu-versie deed: dit is een echt off-canvas paneel
 * dat de HELE viewporthoogte beslaat — inclusief over de header zelf heen —
 * niet een dropdown die er recht onder blijft hangen.
 *
 * x-effect hieronder: zonder dit blijft de pagina er ACHTER het paneel
 * gewoon scrollbaar, ook al dekt het paneel zelf de volledige hoogte af —
 * position:fixed haalt dit paneel uit de flow, maar doet niets aan de
 * scrollbaarheid van de rest van de pagina. overflow-clip (Tailwind-class,
 * niet een losse CSS-regel), NIET overflow-hidden: is op zowel <html> als
 * <body> gezet: sommige browsers (vooral mobiel Safari) respecteren 'm soms
 * alleen op de één, niet de ander. overflow-hidden zou hier ook scrollen
 * blokkeren, maar maakt <html> daarbij een scroll-container — precies wat
 * app.css's eigen `html { overflow-x: clip }` bewust vermijdt, omdat Safari
 * dat dan niet meer als containing block voor position:sticky-kinderen
 * behandelt (zie die comment). Was dit hier ooit overflow-hidden, dan viel
 * de sticky header (own-header) terug op position:static zodra het
 * mobielmenu openging: al voorbij zijn rustpositie gescrold, sprong 'm
 * zichtbaar weg (leek "verdwenen") en weer terug bij het sluiten.
 * overflow-clip blokkeert scrollen net zo goed, zonder die scroll-container
 * te worden. esc_attr(): zelfde reden als render.php's eigen $scroll_expr —
 * geen '>' hier, maar consistent altijd toegepast op elke inline
 * Alpine-expressie in dit thema, zodat een latere toevoeging die wél een
 * '>' bevat niet per ongeluk de hele header-markup corrumpeert (precies wat
 * daar al eens gebeurde).
 */

$cta_button_html = $args['cta_button'] ?? '';
$logo_html = $args['logo'] ?? '';
$scroll_lock_expr = "document.documentElement.classList.toggle('overflow-clip', mobileMenuOpen); document.body.classList.toggle('overflow-clip', mobileMenuOpen)";
?>
<div class="lg:hidden" x-effect="<?= esc_attr($scroll_lock_expr); ?>">
	<!-- Backdrop -->
	<div
		x-show="mobileMenuOpen"
		x-cloak
		x-transition:enter="transition ease-out duration-300"
		x-transition:enter-start="opacity-0"
		x-transition:enter-end="opacity-100"
		x-transition:leave="transition ease-in duration-200"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		@click="mobileMenuOpen = false"
		class="own-mobile-menu-backdrop fixed inset-x-0 z-50 bg-black/50"
		style="top: var(--wp-admin--admin-bar--height, 0px); bottom: 0;"
		aria-hidden="true"></div>

	<!-- Panel -->
	<div
		x-show="mobileMenuOpen"
		x-cloak
		x-transition:enter="transition ease-out duration-300"
		x-transition:enter-start="translate-x-full"
		x-transition:enter-end="translate-x-0"
		x-transition:leave="transition ease-in duration-200"
		x-transition:leave-start="translate-x-0"
		x-transition:leave-end="translate-x-full"
		@keydown.escape.window="mobileMenuOpen = false"
		class="own-mobile-menu fixed right-0 z-50 flex w-full max-w-sm flex-col bg-(--own-header-bg) shadow-lg"
		style="top: var(--wp-admin--admin-bar--height, 0px); bottom: 0;"
		role="dialog"
		aria-modal="true"
		:aria-hidden="(!mobileMenuOpen).toString()">

		<!-- Header -->
		<div class="flex items-center justify-between px-4 sm:px-6 py-2.5 border-b border-black/5">
			<div class="own-mobile-menu__logo">
				<?= $logo_html; ?>
			</div>
			<button
				type="button"
				@click="mobileMenuOpen = false"
				class="own-header-icon-btn -me-2 inline-flex items-center justify-center rounded-full p-2"
				aria-label="<?= esc_attr__('Menu sluiten', 'own-framework'); ?>">
				<?= ownframework_get_icon('close', 'w-5 h-5'); ?>
			</button>
		</div>

		<!-- Navigatie -->
		<nav class="flex-1 flex overflow-hidden">
			<?= Ownframework_Mobile_Drilldown_Walker::render('primary-menu'); ?>
		</nav>

		<!-- Footer: topbalkmenu ("Snel naar") + de header z'n eigen CTA-knop -->
		<div class="shrink-0 border-t border-black/5 p-4 sm:p-6">
			<?php if (has_nav_menu('topbar-menu')) : ?>
				<p class="mb-3 font-semibold"><?= esc_html__('Snel naar:', 'own-framework'); ?></p>
				<?php
				wp_nav_menu([
					'theme_location' => 'topbar-menu',
					'container' => false,
					'fallback_cb' => false,
					'depth' => 1,
					'menu_class' => 'flex flex-wrap gap-2 mb-5',
					'a_class' => 'own-mobile-menu__quicklink',
				]);
				?>
			<?php endif; ?>

			<?php if ($cta_button_html !== '') : ?>
				<div class="mt-3 own-mobile-menu__cta [&_.wp-block-button\_\_link]:flex [&_.wp-block-button\_\_link]:w-full [&_.wp-block-button\_\_link]:justify-center">
					<?= $cta_button_html; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>