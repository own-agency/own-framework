<?php
defined('ABSPATH') || exit();

/**
 * Balk boven acf/footer: een echte, klikbaar bewerkbare InnerBlocks-intro
 * (kop + tekst) links — zelfde patroon als acf/agenda-hero/acf/vacature-hero's
 * eigen intro (zie die render.php's), "level":2 is alleen de STARTwaarde bij
 * het invoegen, geen lock: de native kopregel-toolbar laat een editor 'm
 * daarna vrij tussen H1-H6 wisselen, precies zoals bij elk ander core/heading
 * op de site — en rechts altijd maar één van twee dingen, nooit beide:
 * - Losse site: GEEN keuze — de site-wisselaar kan hier sowieso niet bestaan
 *   (geen netwerk om naar te wisselen), dus is de CTA-knop
 *   (ctaLabel/ctaUrl/ctaOpenInNewTab) het enige dat ooit verschijnt.
 * - Multisite: WEL een keuze — "rightSideContent" (editor.js's "Rechterkant"-
 *   paneel, "switcher"/"cta", default "switcher") laat een editor alsnog voor
 *   de CTA-knop kiezen i.p.v. de wisselaar, bv. voor een netwerksite die geen
 *   zin heeft om naar andere sites te laten doorklikken.
 * editor.js toont het "Rechterkant"-paneel en het CTA-paneel daarom altijd
 * allebei (met helptekst die uitlegt wanneer welke telt) i.p.v. panelen die
 * zouden moeten weten of dít environment multisite is — er is nergens anders
 * in dit thema al een mechanisme om PHP-only state (is_multisite()) naar de
 * editor te sturen, en dat alleen hiervoor optuigen zou meer zijn dan het
 * waard is.
 *
 * De CTA-knop gebruikt letterlijk de site-brede Knop-classes ("wp-element-
 * button wp-block-button__link", zelfde patroon als acf/vacature-apply/acf/
 * agenda-share's eigen "Meld je aan"-knop) i.p.v. eigen kleur-/hoekafrondingattributen:
 * geen eigen per-instance kleur nodig (net als bij die twee blocks), dus volgt
 * hij gewoon Ontwerp → Stijlen → Knoppen — blijft vanzelf in de pas als die
 * stijl ooit verandert, i.p.v. een losse, hier hardcoded look die daarmee uit
 * de pas kan gaan lopen.
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
 * uitzondering hier zoals blocks/agenda-share/render.php's eigen signup-rij
 * (die zichzelf gewoon overslaat, niet het hele block), want die eigen
 * signup_enabled hoort net andersom bij een individueel bericht met eigen
 * content, niet bij een permanent geconfigureerde balk die je aan/uit wil
 * kunnen klikken zonder 'm kwijt te raken.
 */

$enabled = ($block['enabled'] ?? true) !== false;

if (!$enabled && empty($is_preview)) {
	return;
}

$is_multisite = is_multisite();
$sites = [];

if ($is_multisite) {
	$current_blog_id = get_current_blog_id();

	// "Public" (Netwerkbeheer → Sites → Info) filtert hier normaal mee — een
	// site die nog niet als publiek gemarkeerd staat (bv. een klantsite die
	// nog in ontwikkeling is) hoort niet ongevraagd in de wisselaar te
	// verschijnen. Thema opties → Algemeen → "Site-wisselaar: toon ook
	// niet-publieke sites" is een expliciete, tijdelijke ontwikkel-schakelaar
	// om die vlag te negeren terwijl sites nog niet live zijn — zonder dat
	// vergeten wordt uitgezet blijft de wisselaar dus ook niet-publieke sites
	// tonen, dus zet 'm weer uit zodra alles live gaat.
	// "orderby"/"order" hier expliciet op "id"/"ASC" gezet i.p.v. een eigen
	// alfabetische sortering: dat is dezelfde volgorde als Netwerkbeheer →
	// Sites zelf toont zolang daar niet handmatig op een kolom gesorteerd is
	// (WP_MS_Sites_List_Table's eigen default, geen orderby-request-param),
	// dus de wisselaar volgt hier bewust wat een beheerder al als "de"
	// sitevolgorde kent i.p.v. een tweede, eigen volgorde te verzinnen.
	$site_query_args = [
		'archived' => 0,
		'deleted' => 0,
		'spam' => 0,
		'number' => 0,
		'orderby' => 'id',
		'order' => 'ASC',
	];
	if (!get_field('dev_switcher_include_non_public_sites', 'option')) {
		$site_query_args['public'] = 1;
	}

	// De hoofdsite (Netwerkbeheer → Sites, het rijtje zonder "Hoofdsite"-
	// verwijderoptie) krijgt een "isMain"-vlag mee naar de front-end zodat de
	// lijst 'm visueel kan laten opvallen — inhoudelijk nog altijd gewoon een
	// site in de lijst, dus geen aparte plek of eigen sortering, alleen een
	// stijl-hint.
	$main_site_id = get_main_site_id();

	foreach (get_sites($site_query_args) as $site) {
		if ((int) $site->blog_id === $current_blog_id) {
			continue;
		}
		$sites[] = [
			'name' => get_blog_option($site->blog_id, 'blogname'),
			'url' => get_home_url($site->blog_id),
			'isMain' => (int) $site->blog_id === $main_site_id,
		];
	}
}

// Alleen relevant binnen multisite (editor.js's "Rechterkant"-paneel) — een
// losse site heeft deze keuze niet, zie $cta_applicable hieronder.
$right_side_mode = ($block['rightSideContent'] ?? 'switcher') === 'cta' ? 'cta' : 'switcher';

// Toont altijd zodra de installatie multisite is en op "Site-wisselaar" staat
// — ook als er (nog) geen andere sites in het netwerk zijn. De knop opent dan
// een paneel dat alleen "Geen sites gevonden." toont (bestaande $sites-lege
// state, zie de zoekpaneel-markup hieronder), in plaats van de hele knop te
// verbergen: een netwerk dat nu nog 1 site heeft, kan er morgen een tweede bij
// krijgen, en de knop hoeft daar niet expliciet weer aangezet te worden. Geen
// fallback naar de CTA-knop: dat is een aparte, expliciete keuze
// (rightSideContent), geen automatische vervanging.
$show_switcher = $is_multisite && $right_side_mode === 'switcher';
// Altijd de echte, actuele sitenaam — geen los override-veld: de knop moet
// gewoon tonen welke site nu actief is, niet iets waar een editor per ongeluk
// een andere (verouderde) tekst kan laten staan.
$current_site_name = get_bloginfo('name');

$cta_label = trim($block['ctaLabel'] ?? '');
$cta_url = trim($block['ctaUrl'] ?? '');
$cta_new_tab = !empty($block['ctaOpenInNewTab']);
// De CTA-knop is altijd de enige optie op een losse site (geen wisselaar
// mogelijk), en binnen multisite alleen als de editor daar hierboven expliciet
// voor kiest.
$cta_applicable = !$is_multisite || $right_side_mode === 'cta';
$show_cta = $cta_applicable && $cta_label !== '' && $cta_url !== '';
$show_cta_block = $show_cta || (!empty($is_preview) && $cta_applicable);

// Dezelfde core/button style-varianten als een echt ingevoegd Knop-block
// (native "Fill"/"Outline" + dit thema's eigen "Tekstlink"/"Secundair"/
// "Secundair (rand)", inc/core-blocks/button-styles.php) — een expliciete
// whitelist, niet de ruwe attribute-waarde direct in de class, zodat dit
// nooit een andere "is-style-*" class kan uitspugen dan een van deze vijf.
$cta_style_class = match ($block['ctaStyle'] ?? '') {
    'outline' => 'is-style-outline',
    'link' => 'is-style-link',
    'secondary' => 'is-style-secondary',
    'secondary-outline' => 'is-style-secondary-outline',
    default => '',
};

$align = $block['align'] ?? 'wide';
$container_class = match ($align) {
    'full' => 'full-width',
    'wide' => 'wide-width',
    default => 'content-width',
} . ' flex flex-col gap-4 py-6 sm:flex-row sm:items-center sm:justify-between';

// Alleen de sitenaam-tekst (in de knop én in de lijst) — de chevron en de
// secundaire tekst (zoekveld, "Geen sites gevonden.") hebben allemaal al hun
// eigen vaste grijstint en blijven dat, dit is bewust alleen voor de
// sitenamen zelf. Fallback naar het huidige grijs (footer-topbar.css's :root)
// zolang deze niet expliciet gekozen is, zodat een onaangeraakt block precies
// blijft ogen als voorheen.
$switcher_text_color = $block['switcherTextColor'] ?? '';

$wrapper_class = 'own-footer-topbar not-prose gutter-x';
if (!$enabled) {
	// Alleen bereikbaar met $is_preview (de front-end guard hierboven vangt
	// het uitgeschakelde geval al af) — gedimd, niet verborgen, zodat een
	// editor de balk nog gewoon kan blijven bewerken terwijl 'm uit staat.
	$wrapper_class .= ' opacity-50';
}
$wrapper_style = $switcher_text_color !== ''
	? '--own-footer-topbar-switcher-text-color:' . esc_attr($switcher_text_color)
	: '';
$wrapper_attributes = get_block_wrapper_attributes(['class' => $wrapper_class, 'style' => $wrapper_style]);

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
				<button type="button" @click="toggle()" class="own-footer-topbar-switcher-trigger flex w-80 items-center justify-between gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-(--own-footer-topbar-switcher-text-color)">
					<span x-text="'<?= esc_js($current_site_name); ?>'"></span>
					<span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
						<?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
					</span>
				</button>
				<div
					x-show="open"
					x-cloak
					@click.outside="open = false"
					class="own-footer-topbar-switcher-panel absolute right-0 top-full z-20 mt-2 w-80 rounded-xl bg-white">
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
							<a
								:href="site.url"
								class="own-footer-topbar-switcher-option flex items-center rounded-lg px-3 py-2 text-sm text-(--own-footer-topbar-switcher-text-color)"
								:class="{ 'own-footer-topbar-switcher-option--main': site.isMain }"
								x-text="site.name"></a>
						</template>
						<p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-500"><?= esc_html__('Geen sites gevonden.', 'own-framework'); ?></p>
					</div>
				</div>
			</div>
		<?php elseif ($show_cta_block) : ?>
			<div class="<?= esc_attr(trim('wp-block-button shrink-0 ' . $cta_style_class)); ?>">
				<a
					href="<?= esc_url($cta_url ?: '#'); ?>"
					<?php if ($cta_new_tab) : ?>target="_blank" rel="noopener"<?php endif; ?>
					class="wp-block-button__link wp-element-button">
					<?= esc_html($cta_label !== '' ? $cta_label : __('CTA-tekst…', 'own-framework')); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</div>
