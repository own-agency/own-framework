<?php
defined('ABSPATH') || exit();

/**
 * Balk boven acf/footer: een echte, klikbaar bewerkbare InnerBlocks-intro
 * (kop + tekst) links — zelfde patroon als acf/agenda-hero/acf/vacature-hero's
 * eigen intro (zie die render.php's), "level":2 is alleen de STARTwaarde bij
 * het invoegen, geen lock: de native kopregel-toolbar laat een editor 'm
 * daarna vrij tussen H1-H6 wisselen, precies zoals bij elk ander core/heading
 * op de site — en rechts altijd maar één van twee dingen, nooit beide, puur
 * afhankelijk van `is_multisite()`, geen eigen instelling ervoor:
 * - Multisite: de site-wisselaar (doorzoekbare dropdown met de andere sites
 *   in het netwerk, huidige site uitgesloten uit de lijst).
 * - Losse site: de CTA-knop (ctaLabel/ctaUrl/ctaOpenInNewTab, editor.js).
 * editor.js toont daarom ook altijd het CTA-paneel (met helptekst die
 * uitlegt wanneer het wel/niet verschijnt) i.p.v. een dynamisch panel dat zou
 * moeten weten of dít environment multisite is — er is nergens anders in dit
 * thema al een mechanisme om PHP-only state (is_multisite()) naar de editor
 * te sturen, en dat alleen voor deze ene toggle optuigen zou meer zijn dan
 * het waard is.
 *
 * "align" (plain attribute + eigen SelectControl, editor.js — GEEN native
 * toolbar-control, block.json's "supports.align" is false) kiest alleen de
 * breedte van $container_class, dezelfde reden als acf/header-topbar/acf/
 * footer/acf/footer-bottom's eigen render.php-comment: dit block is een
 * bare top-level block in parts/footer.html, dus WordPress' eigen
 * alignment-control verschijnt hier nooit (zie CLAUDE.md's Traps #11).
 * Kleur komt volledig uit dit block's eigen native "Kleuren"-paneel
 * (block.json's "supports.color") — get_block_wrapper_attributes() past die
 * al toe op de wrapper, geen eigen CSS custom properties nodig zoals acf/
 * header-topbar die wel heeft (die heeft er zelf een reden voor, hier niet).
 *
 * "enabled" (default true, editor.js's "Balk tonen") is bewust een losse
 * aan/uit-schakelaar, geen "verwijder het block zelf"-workflow zoals acf/
 * footer-cta's eigen "lock":{"remove":false} (dat block leunt op verwijderen
 * ALS de schakelaar): deze balk blijft hier standaard IN parts/footer.html/
 * -versie-2.html zitten (dus altijd met één druk op de knop terug te zetten),
 * i.p.v. dat een editor 'm eerst weer helemaal opnieuw moet samenstellen
 * (tekst opnieuw typen, CTA/site-wisselaar-instellingen opnieuw invullen) als
 * hij een tijdje niet gebruikt wordt. Uitgeschakeld: front-end toont niets
 * ($enabled check hieronder, vóór alle andere markup), maar de editor blijft
 * 'm gewoon volledig zien (gedimd + een duidelijke notitie) zodat alle
 * instellingen bewaard blijven zichtbaar/bewerkbaar — geen "$is_preview"-
 * uitzondering hier zoals acf/agenda-aanmelden's eigen return, want dat
 * block's eigen signup_enabled hoort net andersom bij een individueel
 * bericht met eigen content, niet bij een permanent geconfigureerde balk die
 * je aan/uit wil kunnen klikken zonder 'm kwijt te raken.
 */

$enabled = ($block['enabled'] ?? true) !== false;

if (!$enabled && empty($is_preview)) {
	return;
}

$is_multisite = is_multisite();
$sites = [];

if ($is_multisite) {
	$current_blog_id = get_current_blog_id();
	foreach (get_sites(['public' => 1, 'archived' => 0, 'deleted' => 0, 'spam' => 0, 'number' => 0]) as $site) {
		if ((int) $site->blog_id === $current_blog_id) {
			continue;
		}
		$sites[] = [
			'name' => get_blog_option($site->blog_id, 'blogname'),
			'url' => get_home_url($site->blog_id),
		];
	}
	usort($sites, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
}

// Alleen tonen als er ook echt iets is om NAARTOE te wisselen — bij precies 1
// site in het netwerk (alleen de huidige) zou de knop een dropdown openen die
// alleen "Geen sites gevonden." kan tonen, nooit bruikbaar.
$show_switcher = $is_multisite && !empty($sites);
// Altijd de echte, actuele sitenaam — geen los override-veld: de knop moet
// gewoon tonen welke site nu actief is, niet iets waar een editor per ongeluk
// een andere (verouderde) tekst kan laten staan.
$current_site_name = get_bloginfo('name');

$cta_label = trim($block['ctaLabel'] ?? '');
$cta_url = trim($block['ctaUrl'] ?? '');
$show_cta = !$is_multisite && $cta_label !== '' && $cta_url !== '';
$cta_new_tab = !empty($block['ctaOpenInNewTab']);
$show_cta_block = $show_cta || (!empty($is_preview) && !$is_multisite);

$align = $block['align'] ?? 'wide';
$container_class = match ($align) {
    'full' => 'full-width',
    'wide' => 'wide-width',
    default => 'content-width',
} . ' flex flex-col gap-4 py-6 sm:flex-row sm:items-center sm:justify-between';

$wrapper_class = 'own-footer-topbar not-prose gutter-x';
if (!$enabled) {
	// Alleen bereikbaar met $is_preview (de front-end guard hierboven vangt
	// het uitgeschakelde geval al af) — gedimd, niet verborgen, zodat een
	// editor de balk nog gewoon kan blijven bewerken terwijl 'm uit staat.
	$wrapper_class .= ' opacity-50';
}
$wrapper_attributes = get_block_wrapper_attributes(['class' => $wrapper_class]);

// Zelfde handgetypte-JSON-string-techniek als acf/agenda-hero/acf/vacature-
// hero's eigen intro — "level":2 is alleen de startwaarde, geen lock op welk
// niveau het daarna wordt. className vervangt SCF's eigen standaard
// acf-innerblocks-container class (Traps #20), niet erbovenop: de enige
// styling-haak die dit blok nodig heeft. [&_:is(h1..h6)]:mt-0!/[&_p]:mb-0!
// halen alleen de kop/paragraaf hun eigen buitenste verticale marge weg, zodat
// deze intro netjes verticaal centreert naast de knop/wisselaar rechts (die
// geen eigen marge hebben) — dezelfde reden als acf/vacatures-uitgelicht's
// en acf/agenda-uitgelicht's eigen intro-className (blocks/vacatures-uitgelicht/
// en blocks/agenda-uitgelicht/render.php).
$text_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":2,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Korte tekst…"}]]\' allowedBlocks=\'["core/heading","core/paragraph"]\' templateLock=\'false\' className=\'own-footer-topbar-text [&_:is(h1,h2,h3,h4,h5,h6)]:mt-0! [&_p]:mb-0!\' />';
?>
<div <?= $wrapper_attributes; ?>>
	<?php if (!$enabled) : ?>
		<p class="own-footer-topbar-disabled-note gutter-x pt-2 text-xs italic opacity-70"><?php esc_html_e('Verborgen op de site — vink "Balk tonen" aan om \'m weer te tonen.', 'own-framework'); ?></p>
	<?php endif; ?>
	<div class="<?= esc_attr($container_class); ?>">
		<?= $text_inner_blocks; ?>

		<?php if ($show_switcher) : ?>
			<div
				class="own-footer-topbar-switcher relative shrink-0"
				x-data="footerTopbarSwitcher"
				data-own-footer-topbar-sites="<?= esc_attr(wp_json_encode($sites)); ?>"
				@keydown.escape.window="open = false">
				<button type="button" @click="toggle()" class="own-footer-topbar-switcher-trigger flex w-72 items-center justify-between gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-gray-800">
					<span x-text="'<?= esc_js($current_site_name); ?>'"></span>
					<span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
						<?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
					</span>
				</button>
				<div
					x-show="open"
					x-cloak
					@click.outside="open = false"
					class="own-footer-topbar-switcher-panel absolute right-0 top-full z-20 mt-2 w-72 rounded-xl bg-white">
					<div class="own-footer-topbar-switcher-search p-2">
						<input
							type="search"
							x-model="query"
							x-ref="search"
							placeholder="<?= esc_attr__('Zoek sites…', 'own-framework'); ?>"
							class="w-full rounded-lg px-3 py-2 text-sm text-gray-800">
					</div>
					<div class="own-footer-topbar-switcher-list max-h-64 overflow-y-auto p-1">
						<template x-for="site in filtered" :key="site.url">
							<a :href="site.url" class="own-footer-topbar-switcher-option flex items-center rounded-lg px-3 py-2 text-sm text-gray-700" x-text="site.name"></a>
						</template>
						<p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-500"><?= esc_html__('Geen sites gevonden.', 'own-framework'); ?></p>
					</div>
				</div>
			</div>
		<?php elseif ($show_cta_block) : ?>
			<a
				href="<?= esc_url($cta_url ?: '#'); ?>"
				<?php if ($cta_new_tab) : ?>target="_blank" rel="noopener"<?php endif; ?>
				class="own-footer-topbar-cta inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-gray-900">
				<?= esc_html($cta_label !== '' ? $cta_label : __('CTA-tekst…', 'own-framework')); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
